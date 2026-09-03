<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Highlight;

/**
 * Renders the highlighting information of a SEAL search result document.
 *
 * SEAL writes the highlighted values into the "_formatted" key of every document. To avoid
 * rendering unescaped HTML from the index in the frontend, the search is executed with the
 * neutral sentinel tags of this class instead of real "<mark>" tags. The values are escaped
 * here and only the sentinels are turned into markup afterwards.
 */
class Highlighting
{
    public const PRE_TAG = '[[seal-hl]]';
    public const POST_TAG = '[[/seal-hl]]';
    public const RESULT_KEY = '_formatted';

    private const ELLIPSIS = '…';

    /**
     * @param array<string, mixed> $document
     */
    public function renderField(array $document, string $field, int $crop = 0): string
    {
        $formatted = null;
        if (isset($document[self::RESULT_KEY]) && \is_array($document[self::RESULT_KEY])) {
            $formattedValue = $document[self::RESULT_KEY][$field] ?? null;
            $formatted = \is_string($formattedValue) ? $formattedValue : null;
        }

        $fallback = $document[$field] ?? '';

        return $this->render($formatted, \is_scalar($fallback) ? (string) $fallback : '', $crop);
    }

    /**
     * @param string|null $formatted Value including the sentinel tags or null if the field did not match
     * @param string $fallback Raw field value, used if there is no highlighting information
     * @param int $crop Maximum length of the rendered text, 0 disables cropping
     */
    public function render(?string $formatted, string $fallback = '', int $crop = 0): string
    {
        $chunks = ($formatted !== null && str_contains($formatted, self::PRE_TAG))
            ? $this->tokenize($formatted)
            : [['text' => $fallback, 'mark' => false]];

        [$chunks, $cropStart, $cropEnd] = $this->crop($chunks, $crop);

        $output = $cropStart ? self::ELLIPSIS . ' ' : '';
        foreach ($chunks as $chunk) {
            $text = htmlspecialchars($chunk['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $output .= $chunk['mark'] ? '<mark>' . $text . '</mark>' : $text;
        }

        return $output . ($cropEnd ? ' ' . self::ELLIPSIS : '');
    }

    /**
     * Splits the value into plain text chunks and chunks that are wrapped in sentinel tags.
     *
     * @return list<array{text: string, mark: bool}>
     */
    private function tokenize(string $value): array
    {
        $parts = preg_split(
            '/(' . preg_quote(self::PRE_TAG, '/') . '|' . preg_quote(self::POST_TAG, '/') . ')/u',
            $value,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );

        if ($parts === false) {
            return [['text' => $value, 'mark' => false]];
        }

        $chunks = [];
        $mark = false;
        foreach ($parts as $part) {
            if ($part === self::PRE_TAG) {
                $mark = true;
                continue;
            }
            if ($part === self::POST_TAG) {
                $mark = false;
                continue;
            }
            if ($part === '') {
                continue;
            }
            $chunks[] = ['text' => $part, 'mark' => $mark];
        }

        return $chunks;
    }

    /**
     * Crops the chunks to the given length, centered around the first highlighted chunk.
     *
     * @param list<array{text: string, mark: bool}> $chunks
     * @return array{0: list<array{text: string, mark: bool}>, 1: bool, 2: bool}
     */
    private function crop(array $chunks, int $length): array
    {
        $plain = implode('', array_column($chunks, 'text'));
        $total = mb_strlen($plain);

        if ($length <= 0 || $total <= $length) {
            return [$chunks, false, false];
        }

        $start = max(0, $this->getFirstMarkOffset($chunks) - intdiv($length, 3));
        if ($start > 0) {
            $nextSpace = mb_strpos($plain, ' ', $start);
            $start = $nextSpace === false ? $start : $nextSpace + 1;
        }

        $end = min($total, $start + $length);
        if ($end < $total) {
            $lastSpace = mb_strrpos(mb_substr($plain, $start, $end - $start), ' ');
            if ($lastSpace !== false && $lastSpace > 0) {
                $end = $start + $lastSpace;
            }
        }

        $sliced = [];
        $position = 0;
        foreach ($chunks as $chunk) {
            $chunkStart = $position;
            $chunkEnd = $position + mb_strlen($chunk['text']);
            $position = $chunkEnd;

            if ($chunkEnd <= $start || $chunkStart >= $end) {
                continue;
            }

            $from = max($start, $chunkStart) - $chunkStart;
            $sliced[] = [
                'text' => mb_substr($chunk['text'], $from, (min($end, $chunkEnd) - $chunkStart) - $from),
                'mark' => $chunk['mark'],
            ];
        }

        return [$sliced, $start > 0, $end < $total];
    }

    /**
     * @param list<array{text: string, mark: bool}> $chunks
     */
    private function getFirstMarkOffset(array $chunks): int
    {
        $offset = 0;
        foreach ($chunks as $chunk) {
            if ($chunk['mark']) {
                return $offset;
            }
            $offset += mb_strlen($chunk['text']);
        }

        return 0;
    }
}
