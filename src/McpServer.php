<?php
declare(strict_types=1);

/**
 * Serveur MCP (Model Context Protocol) minimal, transport « Streamable HTTP » sans flux SSE :
 * chaque requête POST contient un message JSON-RPC (ou un lot) et reçoit une réponse JSON.
 * Il expose en lecture seule le résumé et le cours (entier ou par chapitre) des documents.
 */
final class McpServer
{
    private const PROTOCOLS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public function __construct(private Repository $repo)
    {
    }

    /**
     * Traite le corps d'une requête POST.
     *
     * @return string|null réponse JSON, ou null s'il n'y a rien à répondre (notifications : HTTP 202)
     */
    public function handle(string $body): ?string
    {
        $msg = json_decode($body, true);
        if (!is_array($msg) || $msg === []) {
            return $this->encode($this->error(null, -32700, 'JSON invalide'));
        }
        if (array_is_list($msg)) { // lot de messages
            $out = array_values(array_filter(array_map([$this, 'dispatch'], $msg)));
            return $out ? $this->encode($out) : null;
        }
        $res = $this->dispatch($msg);
        return $res === null ? null : $this->encode($res);
    }

    private function dispatch(mixed $msg): ?array
    {
        if (!is_array($msg)) {
            return $this->error(null, -32600, 'Requête invalide');
        }
        if (!isset($msg['method']) && (array_key_exists('result', $msg) || array_key_exists('error', $msg))) {
            return null; // réponse du client à une requête serveur : ignorée
        }
        if (($msg['jsonrpc'] ?? null) !== '2.0' || !is_string($msg['method'] ?? null)) {
            return $this->error($msg['id'] ?? null, -32600, 'Requête invalide');
        }
        $id = $msg['id'] ?? null;
        $isNotification = !array_key_exists('id', $msg);
        $params = is_array($msg['params'] ?? null) ? $msg['params'] : [];

        try {
            $result = match ($msg['method']) {
                'initialize' => $this->initialize($params),
                'ping' => new stdClass(),
                'tools/list' => ['tools' => $this->tools()],
                'tools/call' => $this->callTool($params),
                default => $isNotification ? null : throw new McpError(-32601, 'Méthode inconnue : ' . $msg['method']),
            };
        } catch (McpError $e) {
            return $isNotification ? null : $this->error($id, $e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            error_log('MCP : ' . $e->getMessage());
            return $isNotification ? null : $this->error($id, -32603, 'Erreur interne');
        }
        return $isNotification || $result === null ? null : ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function initialize(array $params): array
    {
        $asked = (string) ($params['protocolVersion'] ?? '');
        return [
            'protocolVersion' => in_array($asked, self::PROTOCOLS, true) ? $asked : self::PROTOCOLS[0],
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'leitner', 'version' => '1.0.0'],
            'instructions' => 'Contenu des cours de révision (CAP esthétique) : résumé et cours chapitré de chaque document. '
                . 'Commencer par list_documents, puis get_resume, ou list_chapters et get_chapter pour un cours long.',
        ];
    }

    // ---- Outils

    private function tools(): array
    {
        $doc = [
            'id' => ['type' => 'integer', 'description' => 'Identifiant du document (voir list_documents).'],
            'titre' => ['type' => 'string', 'description' => 'Titre exact du document, ou partie du titre si elle est non ambiguë. Ignoré si id est fourni.'],
        ];
        $ro = ['readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false];
        return [
            [
                'name' => 'list_documents',
                'description' => 'Liste les documents de cours avec leur identifiant, leur titre et la disponibilité du résumé et du cours.',
                'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
                'annotations' => $ro,
            ],
            [
                'name' => 'get_resume',
                'description' => 'Renvoie le résumé (Markdown) d\'un document.',
                'inputSchema' => ['type' => 'object', 'properties' => $doc],
                'annotations' => $ro,
            ],
            [
                'name' => 'list_chapters',
                'description' => 'Liste les chapitres du cours d\'un document (numéro, titre, taille en caractères).',
                'inputSchema' => ['type' => 'object', 'properties' => $doc],
                'annotations' => $ro,
            ],
            [
                'name' => 'get_chapter',
                'description' => 'Renvoie le texte (Markdown) d\'un chapitre du cours, désigné par son numéro (voir list_chapters).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $doc + ['chapitre' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Numéro du chapitre, à partir de 1.']],
                    'required' => ['chapitre'],
                ],
                'annotations' => $ro,
            ],
            [
                'name' => 'get_cours',
                'description' => 'Renvoie le cours complet (Markdown) d\'un document. Préférer list_chapters et get_chapter pour un cours long.',
                'inputSchema' => ['type' => 'object', 'properties' => $doc],
                'annotations' => $ro,
            ],
        ];
    }

    private function callTool(array $params): array
    {
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];
        try {
            $text = match ($params['name'] ?? null) {
                'list_documents' => $this->listDocuments(),
                'get_resume' => $this->contenu($args, 'resume', 'résumé'),
                'get_cours' => $this->contenu($args, 'cours', 'cours'),
                'list_chapters' => $this->listChapters($args),
                'get_chapter' => $this->getChapter($args),
                default => throw new McpError(-32602, 'Outil inconnu : ' . (is_string($params['name'] ?? null) ? $params['name'] : '?')),
            };
        } catch (ToolError $e) {
            // Erreur d'exécution de l'outil : renvoyée au LLM, qui peut corriger son appel.
            return ['content' => [['type' => 'text', 'text' => $e->getMessage()]], 'isError' => true];
        }
        return ['content' => [['type' => 'text', 'text' => $text]]];
    }

    private function listDocuments(): string
    {
        $rows = array_map(static fn (array $d): array => [
            'id' => (int) $d['id'],
            'titre' => $d['titre'],
            'resume' => (bool) $d['a_resume'],
            'cours' => (bool) $d['a_cours'],
            'nb_fiches' => (int) $d['nb_fiches'],
        ], $this->repo->documents());
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function contenu(array $args, string $champ, string $nom): string
    {
        $d = $this->document($args);
        $texte = (string) ($d[$champ] ?? '');
        if (trim($texte) === '') {
            throw new ToolError("Le document « {$d['titre']} » n'a pas de $nom.");
        }
        return $texte;
    }

    private function listChapters(array $args): string
    {
        $d = $this->document($args);
        $chapitres = Markdown::chapters((string) ($d['cours'] ?? ''));
        if (!$chapitres) {
            throw new ToolError("Le document « {$d['titre']} » n'a pas de cours.");
        }
        $rows = [];
        foreach ($chapitres as $i => $c) {
            $rows[] = ['chapitre' => $i + 1, 'titre' => $c['titre'], 'caracteres' => mb_strlen($c['texte'])];
        }
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    private function getChapter(array $args): string
    {
        $d = $this->document($args);
        $n = $args['chapitre'] ?? null;
        if (!is_int($n)) {
            throw new ToolError('Le paramètre « chapitre » doit être un entier (numéro à partir de 1).');
        }
        $chapitres = Markdown::chapters((string) ($d['cours'] ?? ''));
        if (!isset($chapitres[$n - 1])) {
            throw new ToolError("Chapitre $n inexistant : le cours « {$d['titre']} » compte " . count($chapitres) . ' chapitre(s).');
        }
        return '## ' . $chapitres[$n - 1]['titre'] . "\n\n" . $chapitres[$n - 1]['texte'];
    }

    /** Résout le document désigné par « id » ou « titre ». */
    private function document(array $args): array
    {
        if (isset($args['id'])) {
            if (!is_int($args['id'])) {
                throw new ToolError('Le paramètre « id » doit être un entier.');
            }
            return $this->repo->document($args['id']) ?? throw new ToolError("Aucun document d'identifiant {$args['id']}.");
        }
        $titre = trim((string) ($args['titre'] ?? ''));
        if ($titre === '') {
            throw new ToolError('Préciser « id » ou « titre » (voir list_documents).');
        }
        if ($exact = $this->repo->documentByTitle($titre)) {
            return $exact;
        }
        $found = $this->repo->searchDocuments($titre);
        if (count($found) === 1) {
            return $found[0];
        }
        throw new ToolError($found
            ? 'Titre ambigu, documents possibles : ' . implode(' ; ', array_map(static fn (array $d): string => "#{$d['id']} {$d['titre']}", $found))
            : "Aucun document ne correspond à « $titre ».");
    }

    // ---- JSON-RPC

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }
}

/** Erreur de protocole JSON-RPC (le code est le code d'erreur). */
final class McpError extends RuntimeException
{
}

/** Erreur d'outil, renvoyée dans le résultat avec isError. */
final class ToolError extends RuntimeException
{
}
