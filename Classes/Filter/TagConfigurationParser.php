<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

class TagConfigurationParser
{
    /**
     * Parst den Tag-Konfigurationstext in ein strukturiertes Array.
     *
     * @param string $configuration Mehrzeiliger Text im Format "wert=Label"
     * @return array<int, array{value: string, label: string}>
     */
    public function parse(string $configuration): array
    {
        if (trim($configuration) === '') {
            return [];
        }

        $lines = preg_split('/\r?\n/', $configuration);
        if ($lines === false) {
            return [];
        }

        $tags = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            $equalsPosition = strpos($trimmedLine, '=');
            if ($equalsPosition === false) {
                $value = $trimmedLine;
                $label = $trimmedLine;
            } else {
                $value = trim(substr($trimmedLine, 0, $equalsPosition));
                $label = trim(substr($trimmedLine, $equalsPosition + 1));
            }

            if ($value === '') {
                continue;
            }

            $tags[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $tags;
    }

    /**
     * Formatiert ein Array von Tag-Einträgen zurück in das Textformat.
     *
     * @param array<int, array{value: string, label: string}> $tags
     * @return string
     */
    public function format(array $tags): string
    {
        $lines = [];
        foreach ($tags as $tag) {
            $value = $tag['value'];
            $label = $tag['label'];

            if ($value === $label) {
                $lines[] = $value;
            } else {
                $lines[] = $value . '=' . $label;
            }
        }

        return implode("\n", $lines);
    }
}
