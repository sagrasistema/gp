<?php

declare(strict_types=1);

/**
 * Clase de servicio para parsear y renderizar textos planos de auditoría en HTML.
 * Compatible con PHP 8.x (Estándar PSR-12).
 */
class AuditTextRenderer
{
    /**
     * Procesa una cadena de texto plano proveniente de BD y la transforma en HTML maquetado.
     *
     * @param string|null $plainText Texto plano extraído de la base de datos.
     * @return string Código HTML generado y sanitizado contra XSS.
     */
    public static function render(?string $plainText): string
    {
        if ($plainText === null || trim($plainText) === '') {
            return '<em class="text-muted">No hay información registrada para esta prueba.</em>';
        }

        // Sanitización estricta anti-XSS antes de manipular la cadena
        $safeText = htmlspecialchars(trim($plainText), ENT_QUOTES, 'UTF-8');

        // Limpieza de comillas dobles excesivas resultantes de exportaciones CSV/Excel
        if (str_starts_with($safeText, '&quot;') && str_ends_with($safeText, '&quot;')) {
            $safeText = mb_substr($safeText, 6, -6);
        }
        $safeText = str_replace('&quot;&quot;', '&quot;', $safeText);

        // Identificación de patrones clave para insertar delimitadores de estructura
        $patterns = [
            '/\s*(PRUEBA\s+\d+:)/u'            => "\n[HEADER]$1",
            '/\s*(Actividad\s+\d+:)/u'         => "\n[SUBHEADER]$1",
            '/\s*(•\s*Objetivo:)/u'            => "\n[OBJETIVO]$1",
            '/\s*(•\s*¿Qué significa\?:)/u'    => "\n[EXPLANATION]$1",
            '/\s*(•\s*Instrucción Técnica:)/u' => "\n[INSTRUCTION]$1",
            '/\s*(•\s*Respuesta Sugerida:)/u'  => "\n[ANSWER]$1",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $safeText = (string) preg_replace($pattern, $replacement, $safeText);
        }

        // División del contenido por líneas y formateo visual dinámico
        $lines = array_filter(
            explode("\n", $safeText),
            static fn(string $line): bool => trim($line) !== ''
        );

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