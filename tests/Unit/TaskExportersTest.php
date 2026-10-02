<?php

namespace Tests\Unit;

use App\Domain\Task\Export\CsvTaskExporter;
use App\Domain\Task\Export\JsonTaskExporter;
use App\Domain\Task\Export\MultiFormatTaskExporter;
use App\Domain\Task\Export\PdfTaskExporter;
use App\Domain\Task\Export\XmlTaskExporter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TaskExportersTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $tasks = [
        ['title' => 'First', 'status' => 'todo'],
        ['title' => 'Second', 'status' => 'done'],
    ];

    public function test_csv_export(): void
    {
        $this->assertSame("title,status\nFirst,todo\nSecond,done", (new CsvTaskExporter)->export($this->tasks));
    }

    public function test_csv_export_quotes_values_containing_commas_quotes_and_newlines(): void
    {
        $csv = (new CsvTaskExporter)->export([['title' => "Fix \"login\", then deploy\nsoon", 'status' => 'todo']]);

        $parsed = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $parsed[] = $row;
        }

        $this->assertSame([['title', 'status'], ["Fix \"login\", then deploy\nsoon", 'todo']], $parsed);
    }

    public function test_json_export_round_trips(): void
    {
        $this->assertSame($this->tasks, json_decode((new JsonTaskExporter)->export($this->tasks), true));
    }

    public function test_xml_export_contains_each_task_and_escapes_values(): void
    {
        $xml = simplexml_load_string((new XmlTaskExporter)->export([['title' => 'A & B', 'status' => 'todo']]));

        $this->assertSame('A & B', (string) $xml->task->title);
        $this->assertSame('todo', (string) $xml->task->status);
    }

    public function test_pdf_export_has_a_task_limit(): void
    {
        $exporter = new PdfTaskExporter;
        $tooMany = array_fill(0, 101, ['title' => 'x', 'status' => 'todo']);

        $this->assertSame('PDF content for 2 tasks', $exporter->export($this->tasks));
        $this->assertTrue($exporter->canExport(array_slice($tooMany, 0, 100)));
        $this->assertFalse($exporter->canExport($tooMany));

        $this->expectException(RuntimeException::class);
        $exporter->export($tooMany);
    }

    public function test_multi_format_exporter_skips_formats_that_cannot_handle_the_tasks(): void
    {
        $tooMany = array_fill(0, 101, ['title' => 'x', 'status' => 'todo']);

        $results = (new MultiFormatTaskExporter)->exportAllFormats($tooMany, [new PdfTaskExporter, new CsvTaskExporter]);

        $this->assertStringStartsWith('Skipped', $results[0]);
        $this->assertStringStartsWith('title,status', $results[1]);
    }
}
