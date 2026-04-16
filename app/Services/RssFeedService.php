<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RssFeedService
{
    /**
     * Récupère et parse un flux RSS 2.0.
     *
     * @return array Liste d'items [{title, description, link, pubDate, guid, image_url}]
     */
    public function fetch(string $url): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'CivicDash/2.2 RSS Reader'])
                ->get($url);

            if ($response->failed()) {
                Log::warning("RSS: échec fetch {$url}", ['status' => $response->status()]);

                return [];
            }

            return $this->parse($response->body());
        } catch (\Throwable $e) {
            Log::error("RSS: erreur fetch {$url}", ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function parse(string $xml): array
    {
        $items = [];

        try {
            $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (! $feed || ! isset($feed->channel->item)) {
                return [];
            }

            foreach ($feed->channel->item as $item) {
                $imageUrl = null;
                if (isset($item->enclosure)) {
                    $type = (string) $item->enclosure['type'];
                    if (str_starts_with($type, 'image/')) {
                        $imageUrl = (string) $item->enclosure['url'];
                    }
                }

                $mediaNamespaces = $item->getNameSpaces(true);
                if (! $imageUrl && isset($mediaNamespaces['media'])) {
                    $media = $item->children($mediaNamespaces['media']);
                    if (isset($media->content)) {
                        $imageUrl = (string) $media->content->attributes()['url'];
                    }
                }

                $items[] = [
                    'title' => trim((string) $item->title),
                    'description' => strip_tags(trim((string) $item->description)),
                    'link' => trim((string) $item->link),
                    'pubDate' => (string) $item->pubDate,
                    'guid' => trim((string) ($item->guid ?? $item->link)),
                    'image_url' => $imageUrl,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('RSS: erreur parsing', ['error' => $e->getMessage()]);
        }

        return $items;
    }
}
