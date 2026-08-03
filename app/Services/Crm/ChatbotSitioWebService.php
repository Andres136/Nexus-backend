<?php

namespace App\Services\Crm;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ChatbotSitioWebService
{
    private const MAX_REDIRECTS = 3;
    private const MAX_BODY_BYTES = 1_000_000;
    private const MAX_CONTEXT_CHARS = 15_000;

    public function extraer(string $url): string
    {
        $response = $this->descargar($url);
        $contentType = strtolower($response->header('Content-Type', ''));

        if (!str_contains($contentType, 'text/html') && !str_contains($contentType, 'text/plain')) {
            throw ValidationException::withMessages([
                'sitio_web_url' => 'La dirección debe responder con una página HTML pública.',
            ]);
        }

        $html = substr($response->body(), 0, self::MAX_BODY_BYTES);
        $texto = $this->textoVisible($html);

        if (mb_strlen($texto) < 20) {
            throw ValidationException::withMessages([
                'sitio_web_url' => 'No se encontró suficiente contenido legible en la página.',
            ]);
        }

        return mb_substr($texto, 0, self::MAX_CONTEXT_CHARS);
    }

    private function descargar(string $url, int $redirects = 0)
    {
        $this->validarUrlPublica($url);

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->withHeaders(['User-Agent' => 'NexusChatbot/1.0'])
            ->withOptions(['allow_redirects' => false])
            ->get($url);

        if ($response->redirect()) {
            if ($redirects >= self::MAX_REDIRECTS) {
                throw ValidationException::withMessages([
                    'sitio_web_url' => 'La página tiene demasiadas redirecciones.',
                ]);
            }

            $destino = $response->header('Location');
            if ($destino && str_starts_with($destino, '/')) {
                $partes = parse_url($url);
                $puerto = isset($partes['port']) ? ':'.$partes['port'] : '';
                $destino = "{$partes['scheme']}://{$partes['host']}{$puerto}{$destino}";
            }

            if (!$destino || !filter_var($destino, FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages([
                    'sitio_web_url' => 'La página redirige a una dirección no válida.',
                ]);
            }

            return $this->descargar($destino, $redirects + 1);
        }

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'sitio_web_url' => "No se pudo leer la página (HTTP {$response->status()}).",
            ]);
        }

        return $response;
    }

    private function validarUrlPublica(string $url): void
    {
        $partes = parse_url($url);
        $scheme = strtolower($partes['scheme'] ?? '');
        $host = $partes['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw ValidationException::withMessages([
                'sitio_web_url' => 'Ingresa una dirección web válida que comience por http:// o https://.',
            ]);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($ips === []) {
            throw ValidationException::withMessages([
                'sitio_web_url' => 'No se pudo encontrar el dominio indicado.',
            ]);
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw ValidationException::withMessages([
                    'sitio_web_url' => 'La dirección debe pertenecer a un sitio web público.',
                ]);
            }
        }
    }

    private function textoVisible(string $html): string
    {
        $anterior = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $fragmentos = [];
        $titulo = trim($dom->getElementsByTagName('title')->item(0)?->textContent ?? '');
        if ($titulo !== '') {
            $fragmentos[] = "Título: {$titulo}";
        }

        foreach ($dom->getElementsByTagName('meta') as $meta) {
            $nombre = strtolower($meta->getAttribute('name') ?: $meta->getAttribute('property'));
            if (in_array($nombre, ['description', 'keywords', 'og:title', 'og:description'], true)) {
                $contenido = trim($meta->getAttribute('content'));
                if ($contenido !== '') {
                    $fragmentos[] = $contenido;
                }
            }
        }

        foreach (['script', 'style', 'svg', 'nav', 'footer'] as $tag) {
            $nodos = [];
            foreach ($dom->getElementsByTagName($tag) as $nodo) {
                $nodos[] = $nodo;
            }
            foreach ($nodos as $nodo) {
                $nodo->parentNode?->removeChild($nodo);
            }
        }

        $contenidoVisible = html_entity_decode($dom->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $fragmentos[] = $contenidoVisible;
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $texto = implode(' ', array_unique(array_filter($fragmentos)));

        return trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
    }
}
