<?php

namespace App\Services;

class ExportService
{
    /**
     * Convert array data to CSV format
     *
     * @param array $headers
     * @param array $rows
     * @return string
     */
    public static function toCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Generate CSV response for download
     *
     * @param string $filename
     * @param array $headers
     * @param array $rows
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function downloadCsv(string $filename, array $headers, array $rows)
    {
        $csv = self::toCsv($headers, $rows);
        
        return response()->streamDownload(function() use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate CSV response for download with flat data array (no headers)
     *
     * @param string $filename
     * @param array $data
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function downloadCsvFlat(string $filename, array $data)
    {
        return response()->streamDownload(function() use ($data) {
            $output = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($output, is_array($row) ? $row : [$row]);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
