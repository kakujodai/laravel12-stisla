<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\FileUpload;

class PostgresImportController extends Controller
{
    public function create()
    {
        return view('profile.postgres-form');
    }

    /*public function test(Request $request)
    {
        $data = $request->validate([
            'pg_host' => 'required|string|max:255',
            'pg_port' => 'required|integer|min:1|max:65535',
            'pg_database' => 'required|string|max:255',
            'pg_username' => 'required|string|max:255',
            'pg_password' => 'nullable|string|max:255',
            'pg_sslmode' => 'nullable|string|in:disable,prefer,require',
        ]);

        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
                $data['pg_host'],
                (int)$data['pg_port'],
                $data['pg_database'],
                $data['pg_sslmode'] ?? 'prefer'
            );

            $pdo = new \PDO($dsn, $data['pg_username'], $data['pg_password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            $pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['pg_host' => 'Connection failed: '.$e->getMessage()]);
        }

        return back()->with('success', 'Connection successful!');
    }*/

    public function schemas(Request $request)
    {
        $data = $request->validate([
            'pg_host' => 'required|string',
            'pg_port' => 'required|integer',
            'pg_database' => 'required|string',
            'pg_username' => 'required|string',
            'pg_password' => 'nullable|string',
            'pg_sslmode' => 'nullable|string',
        ]);

        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
                $data['pg_host'],
                (int)$data['pg_port'],
                $data['pg_database'],
                $data['pg_sslmode'] ?? 'prefer'
            );

            $pdo = new \PDO($dsn, $data['pg_username'], $data['pg_password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $schemas = $pdo->query("
                SELECT schema_name
                FROM information_schema.schemata
                WHERE schema_name NOT IN ('pg_catalog', 'information_schema')
                    AND schema_name NOT LIKE 'pg_%'
                ORDER BY schema_name
            ")->fetchAll(\PDO::FETCH_COLUMN);

        } catch (\Throwable $e) {
            return back()->withErrors(['pg_host' => 'Failed: '.$e->getMessage()]);
        }

        return view('profile.postgres-select-schema', [
            'schemas' => $schemas,
            'connection' => $data,
        ]);

    }
    public function tables(Request $request)
    {
        $data = $request->validate([
            'pg_host' => 'required|string|max:255',
            'pg_port' => 'required|integer|min:1|max:65535',
            'pg_database' => 'required|string|max:255',
            'pg_username' => 'required|string|max:255',
            'pg_password' => 'nullable|string|max:255',
            'pg_sslmode' => 'nullable|string|in:disable,prefer,require',
            'schema' => 'required|string|max:255',
        ]);

        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
                $data['pg_host'],
                (int)$data['pg_port'],
                $data['pg_database'],
                $data['pg_sslmode'] ?? 'prefer'
            );

            $pdo = new \PDO($dsn, $data['pg_username'], $data['pg_password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->prepare("
                SELECT DISTINCT c.table_name
                FROM information_schema.columns c
                JOIN information_schema.tables t
                ON t.table_schema = c.table_schema
                AND t.table_name = c.table_name
                WHERE c.table_schema = :schema
                AND t.table_type = 'BASE TABLE'
                AND c.udt_name IN ('geometry','geography')
                ORDER BY c.table_name
            ");
            $stmt->execute(['schema' => $data['schema']]);
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($tables)) {
                return back()->withErrors(['schema' => 'No GIS tables (geometry/geography columns) were found in this schema.']);
            }

        } catch (\Throwable $e) {
            return back()->withErrors(['schema' => 'Failed: '.$e->getMessage()]);
        }
        
        session([
            'pg_connection' => [
                'pg_host' => $data['pg_host'],
                'pg_port' => $data['pg_port'],
                'pg_database' => $data['pg_database'],
                'pg_username' => $data['pg_username'],
                'pg_password' => $data['pg_password'],
                'pg_sslmode' => $data['pg_sslmode'] ?? 'prefer',
            ],
            'pg_schema' => $data['schema'],
            'pg_tables' => $tables,
        ]);

    return redirect()->route('profile.postgres.tables.show');

    }
    public function showTables()
    {
        $tables = session('pg_tables');
        $schema = session('pg_schema');
        $connection = session('pg_connection');

        if (!$tables || !$schema || !$connection) {
            return redirect()->route('profile.postgres.form');
        }

        return view('profile.postgres-select-table', compact('tables', 'schema', 'connection'));
    }
    public function import(Request $request)
    {
        $data = $request->validate([
            'pg_host' => 'required|string|max:255',
            'pg_port' => 'required|integer|min:1|max:65535',
            'pg_database' => 'required|string|max:255',
            'pg_username' => 'required|string|max:255',
            'pg_password' => 'nullable|string|max:255',
            'pg_sslmode' => 'nullable|string|in:disable,prefer,require',
            'schema' => 'required|string|max:255',
            'table' => 'required|string|max:255',
        ]);

        $userId = auth()->id();
        $upload_limit = 52428800; // 50mb
        $path = "users/{$userId}";

        $schema = $data['schema'];
        $table = $data['table'];

        $isSafeIdent = function (string $s): bool {
            return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $s);
        };

        if (!$isSafeIdent($schema) || !$isSafeIdent($table)) {
            return back()->withErrors(['table' => 'Invalid schema/table name.']);
        }

        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
                $data['pg_host'],
                (int)$data['pg_port'],
                $data['pg_database'],
                $data['pg_sslmode'] ?? 'prefer'
            );

            $pdo = new \PDO($dsn, $data['pg_username'], $data['pg_password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->prepare("
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = :schema
                AND table_name = :table
                AND table_type = 'BASE TABLE'
                LIMIT 1
            ");
            $stmt->execute(['schema' => $schema, 'table' => $table]);
            if (!$stmt->fetchColumn()) {
                return back()->withErrors(['table' => 'That table was not found.']);
            }

            $stmt = $pdo->prepare("
                SELECT column_name, udt_name
                FROM information_schema.columns
                WHERE table_schema = :schema
                AND table_name = :table
                ORDER BY ordinal_position
            ");
            $stmt->execute(['schema' => $schema, 'table' => $table]);
            $cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!$cols) {
                return back()->withErrors(['table' => 'Could not read table columns.']);
            }

            $geomCol = null;
            foreach ($cols as $c) {
                $udt = strtolower($c['udt_name'] ?? '');
                if ($udt === 'geometry' || $udt === 'geography') {
                    $geomCol = $c['column_name'];
                    break;
                }
            }

            $propCols = [];
            foreach ($cols as $c) {
                $col = $c['column_name'];
                if ($geomCol !== null && $col === $geomCol) continue;
                $propCols[] = $col;
            }

            if ($geomCol === null) {
                return back()->withErrors(['table' => 'No geometry/geography column found in this table.']);
            }

            $qi = function (string $ident): string {
                return '"' . str_replace('"', '""', $ident) . '"';
            };

            $schemaQ = $qi($schema);
            $tableQ  = $qi($table);
            $geomQ   = $qi($geomCol);

            $fullName = "{$schemaQ}.{$tableQ}";

            $sql = "
                SELECT jsonb_build_object(
                    'type','FeatureCollection',
                    'features', COALESCE(jsonb_agg(
                        jsonb_build_object(
                            'type','Feature',
                            'geometry', CASE
                                WHEN {$geomQ} IS NULL THEN NULL
                                ELSE ST_AsGeoJSON(ST_Transform({$geomQ}, 4326))::jsonb
                            END,
                            'properties', (to_jsonb(t) - :geom_col)
                        )
                    ), '[]'::jsonb)
                )::text AS geojson
                FROM {$fullName} t
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['geom_col' => $geomCol]);
            $geojsonText = $stmt->fetchColumn();

            if (!$geojsonText) {
                return back()->withErrors(['table' => 'Export returned no data.']);
            }

            if (strlen($geojsonText) > $upload_limit) {
                $mb = round($upload_limit / 1024 / 1024);
                return back()->withErrors(['table' => "This dataset is too large. {$mb}MB is the maximum."]);
            }

            $decoded = json_decode($geojsonText, true);
            if (!is_array($decoded) || ($decoded['type'] ?? null) !== 'FeatureCollection') {
                return back()->withErrors(['table' => 'Exported GeoJSON is invalid.']);
            }

            $geojson_chart_metadata = ['x_axis' => [], 'y_axis' => []];

            if (!empty($decoded['features']) && isset($decoded['features'][0]['properties']) && is_array($decoded['features'][0]['properties'])) {
                foreach ($decoded['features'][0]['properties'] as $key => $value) {
                    $geojson_chart_metadata['x_axis'][] = $key;
                    if (is_numeric($value)) {
                        $geojson_chart_metadata['y_axis'][] = $key;
                    }
                }
            } else {
                foreach ($propCols as $c) {
                    $geojson_chart_metadata['x_axis'][] = $c;
                }
            }

            $safeBase = preg_replace('/[^A-Za-z0-9_]/', '_', "{$schema}_{$table}");
            $filename = "{$safeBase}.geojson";

            if (FileUpload::where('filename', $filename)->where('user_id', $userId)->exists()) {
                $filename = "{$safeBase}_" . date('Ymd_His') . ".geojson";
            }

            Storage::put("{$path}/{$filename}", $geojsonText);

            $file_upload = new \App\Models\FileUpload;
            $file_upload->user_id = $userId;
            $file_upload->filename = $filename;
            $file_upload->geojson = $geojsonText;
            $file_upload->properties_metadata = json_encode($geojson_chart_metadata);
            $file_upload->md5 = md5($geojsonText);
            $file_upload->title = "{$schema}.{$table}";
            $file_upload->save();

        } catch (\Throwable $e) {
            return back()->withErrors(['table' => 'Import failed: '.$e->getMessage()]);
        }

        return redirect()->route('profile.upload')->with('success', 'Imported successfully!');
    }


}

