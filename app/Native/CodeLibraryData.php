<?php

declare(strict_types=1);

namespace App\Native;

/**
 * Singleton data store for the Code Library tool.
 *
 * Persists to a JSON file in the system temp directory.  Seed data is written
 * on first run so the panel is never empty.
 */
final class CodeLibraryData
{
    private static ?self $instance = null;

    private string $path;
    /** @var array{langs: list<array{type:string,show:bool}>, group: list<array{id:string,type:string,name:string}>, items: list<array{id:string,groupID:string,fromType:string,name:string,comment:string,value:string,toValue:string}>} */
    private array $data;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /** Reset singleton (for testing). */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    private function __construct()
    {
        $this->path = sys_get_temp_dir() . '/flyenv_code_library.json';
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->path)) {
            $json = file_get_contents($this->path);
            $decoded = $json !== false ? json_decode($json, true) : null;
            $this->data = (is_array($decoded) && isset($decoded['langs'], $decoded['group'], $decoded['items']))
                ? $decoded
                : $this->seedData();
        } else {
            $this->data = $this->seedData();
            $this->save();
        }
    }

    public function save(): void
    {
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // ── Seed ──────────────────────────────────────────────────────────

    /** @return array{langs:list,group:list,items:list} */
    private function seedData(): array
    {
        $g1 = $this->uuid();
        return [
            'langs' => [
                ['type' => 'erlang', 'show' => true],
                ['type' => 'golang', 'show' => true],
                ['type' => 'java', 'show' => true],
                ['type' => 'javascript', 'show' => true],
                ['type' => 'perl', 'show' => true],
                ['type' => 'php', 'show' => true],
                ['type' => 'python', 'show' => true],
                ['type' => 'ruby', 'show' => true],
                ['type' => 'rust', 'show' => true],
                ['type' => 'typescript', 'show' => true],
            ],
            'group' => [
                ['id' => $g1, 'type' => 'php', 'name' => 'Database'],
            ],
            'items' => [
                [
                    'id' => $this->uuid(), 'groupID' => $g1, 'fromType' => 'php',
                    'name' => 'PDO Connect', 'comment' => 'Connect via PDO',
                    'value' => "<?php\n\$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');\n",
                    'toValue' => '',
                ],
                [
                    'id' => $this->uuid(), 'groupID' => '', 'fromType' => 'php',
                    'name' => 'cURL GET', 'comment' => '',
                    'value' => "<?php\n\$ch = curl_init('https://example.com');\ncurl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n\$data = curl_exec(\$ch);\ncurl_close(\$ch);\n",
                    'toValue' => '',
                ],
                [
                    'id' => $this->uuid(), 'groupID' => '', 'fromType' => 'python',
                    'name' => 'Requests GET', 'comment' => '',
                    'value' => "import requests\nr = requests.get('https://example.com')\nprint(r.text)\n",
                    'toValue' => '',
                ],
            ],
        ];
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        );
    }

    // ── Getters ───────────────────────────────────────────────────────

    /** @return list<array{type:string,show:bool}> */
    public function getLangs(): array
    {
        return $this->data['langs'];
    }

    /** @return list<array{id:string,type:string,name:string}> */
    public function getGroups(string $type): array
    {
        return array_values(array_filter($this->data['group'], static fn (array $g): bool => $g['type'] === $type));
    }

    /** @return list<array> Ungrouped items for a language. */
    public function getUngroupedItems(string $type): array
    {
        return array_values(array_filter(
            $this->data['items'],
            static fn (array $i): bool => $i['fromType'] === $type && $i['groupID'] === '',
        ));
    }

    /** @return list<array> Items belonging to a group. */
    public function getGroupItems(string $groupId): array
    {
        return array_values(array_filter(
            $this->data['items'],
            static fn (array $i): bool => $i['groupID'] === $groupId,
        ));
    }

    public function getItem(string $id): ?array
    {
        foreach ($this->data['items'] as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function getGroup(string $id): ?array
    {
        foreach ($this->data['group'] as $g) {
            if ($g['id'] === $id) {
                return $g;
            }
        }
        return null;
    }

    // ── Group CRUD ────────────────────────────────────────────────────

    public function addGroup(string $type, string $name): string
    {
        $id = $this->uuid();
        $this->data['group'][] = ['id' => $id, 'type' => $type, 'name' => $name];
        $this->save();
        return $id;
    }

    public function updateGroup(string $id, string $name): void
    {
        foreach ($this->data['group'] as &$g) {
            if ($g['id'] === $id) {
                $g['name'] = $name;
                break;
            }
        }
        $this->save();
    }

    public function deleteGroup(string $id): void
    {
        $this->data['group'] = array_values(array_filter(
            $this->data['group'],
            static fn (array $g): bool => $g['id'] !== $id,
        ));
        foreach ($this->data['items'] as &$i) {
            if ($i['groupID'] === $id) {
                $i['groupID'] = '';
            }
        }
        $this->save();
    }

    public function moveGroupToTop(string $id): void
    {
        foreach ($this->data['group'] as $idx => $g) {
            if ($g['id'] === $id) {
                $item = array_splice($this->data['group'], $idx, 1)[0];
                array_unshift($this->data['group'], $item);
                break;
            }
        }
        $this->save();
    }

    // ── Item CRUD ─────────────────────────────────────────────────────

    public function addItem(string $groupID, string $fromType, string $name, string $comment, string $value, string $toValue = ''): string
    {
        $id = $this->uuid();
        $this->data['items'][] = [
            'id' => $id, 'groupID' => $groupID, 'fromType' => $fromType,
            'name' => $name, 'comment' => $comment, 'value' => $value, 'toValue' => $toValue,
        ];
        $this->save();
        return $id;
    }

    public function updateItem(string $id, array $fields): void
    {
        foreach ($this->data['items'] as &$i) {
            if ($i['id'] === $id) {
                $i = array_merge($i, $fields);
                break;
            }
        }
        $this->save();
    }

    public function deleteItem(string $id): void
    {
        $this->data['items'] = array_values(array_filter(
            $this->data['items'],
            static fn (array $i): bool => $i['id'] !== $id,
        ));
        $this->save();
    }

    public function deleteItems(array $ids): void
    {
        $flip = array_flip($ids);
        $this->data['items'] = array_values(array_filter(
            $this->data['items'],
            static fn (array $i): bool => !isset($flip[$i['id']]),
        ));
        $this->save();
    }

    public function moveItems(array $ids, string $groupId): void
    {
        $flip = array_flip($ids);
        foreach ($this->data['items'] as &$i) {
            if (isset($flip[$i['id']])) {
                $i['groupID'] = $groupId;
            }
        }
        $this->save();
    }

    public function moveItemToTop(string $id): void
    {
        foreach ($this->data['items'] as $idx => $i) {
            if ($i['id'] === $id) {
                $item = array_splice($this->data['items'], $idx, 1)[0];
                array_unshift($this->data['items'], $item);
                break;
            }
        }
        $this->save();
    }

    // ── Language toggle ───────────────────────────────────────────────

    public function toggleLangShow(string $type): void
    {
        foreach ($this->data['langs'] as &$l) {
            if ($l['type'] === $type) {
                $l['show'] = !$l['show'];
                break;
            }
        }
        $this->save();
    }
}
