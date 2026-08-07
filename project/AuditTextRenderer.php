<?php

declare(strict_types=1);

namespace App\Services;

class AuditTextRenderer
{
    /**
     * Procesa texto plano desde la BD y lo transforma dinámicamente en HTML
     * estructurado sin modificar la BD original.
     */
    public static function render(?string $plainText): string
    {
        if ($plainText === null || trim($plainText) === '') {
            return '<em class="text-muted">No hay información registrada para esta prueba.</em>';
        }

        // 1. Sanitización anti-XSS antes de manipular
        $safeText = htmlspecialchars(trim($plainText), ENT_QUOTES, 'UTF-8');

        // Limpieza de comillas excesivas de exportaciones/importaciones previa
        if (str_starts_with($safeText, '&quot;') && str_ends_with($safeText, '&quot;')) {
            $safeText = mb_substr($safeText, 6, -6);
        }
        $safeText = str_replace('&quot;&quot;', '&quot;', $safeText);

        // 2. Normalización de patrones para aislar secciones principales
        $patterns = [
            '/\s*(PRUEBA\s+\d+:)/u'           => "\n[HEADER]$1",
            '/\s*(Actividad\s+\d+:)/u'        => "\n[SUBHEADER]$1",
            '/\s*(•\s*Objetivo:)/u'           => "\n[OBJETIVO]$1",
            '/\s*(•\s*¿Qué significa\?:)/u'   => "\n[EXPLANATION]$1",
            '/\s*(•\s*Instrucción Técnica:)/u'=> "\n[INSTRUCTION]$1",
            '/\s*(•\s*Respuesta Sugerida:)/u' => "\n[ANSWER]$1",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $safeText = (string) preg_replace($pattern, $replacement, $safeText);
        }

        // 3. Procesamiento por líneas e inyección de clases CSS
        $lines = array_filter(explode("\n", $safeText), static fn(string $line) => trim($line) !== '');
        $outputHtml = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, '[HEADER]')) {
                $content = str_replace('[HEADER]', '', $line);
                $outputHtml[] = sprintf('<div class="audit-badge-header">%s</div>', $content);
            } elseif (str_starts_with($line, '[SUBHEADER]')) {
                $content = str_replace('[SUBHEADER]', '', $line);
                $outputHtml[] = sprintf(
                    '<h4 class="audit-activity-title"><i class="ri-checkbox-circle-fill"></i> %s</h4>',
                    $content
                );
            } elseif (str_starts_with($line, '[OBJETIVO]')) {
                $content = trim(str_replace(['[OBJETIVO]', '• Objetivo:'], '', $line));
                $outputHtml[] = sprintf(
                    '<div class="audit-item"><span class="audit-label text-primary">• Objetivo:</span> %s</div>',
                    $content
                );
            } elseif (str_starts_with($line, '[EXPLANATION]')) {
                $content = trim(str_replace(['[EXPLANATION]', '• ¿Qué significa?:'], '', $line));
                $outputHtml[] = sprintf(
                    '<div class="audit-callout audit-callout-info"><strong><i class="ri-question-line"></i> ¿Qué significa?:</strong> %s</div>',
                    $content
                );
            } elseif (str_starts_with($line, '[INSTRUCTION]')) {
                $content = trim(str_replace(['[INSTRUCTION]', '• Instrucción Técnica:'], '', $line));
                $outputHtml[] = sprintf(
                    '<div class="audit-item"><span class="audit-label text-secondary">• Instrucción Técnica:</span> %s</div>',
                    $content
                );
            } elseif (str_starts_with($line, '[ANSWER]')) {
                $content = trim(str_replace(['[ANSWER]', '• Respuesta Sugerida:'], '', $line));
                $outputHtml[] = sprintf(
                    '<div class="audit-callout audit-callout-success"><strong><i class="ri-lightbulb-line"></i> Respuesta Sugerida:</strong> %s</div>',
                    $content
                );
            } else {
                $outputHtml[] = sprintf('<p class="audit-paragraph">%s</p>', $line);
            }
        }

        return implode("\n", $outputHtml);
    }
}