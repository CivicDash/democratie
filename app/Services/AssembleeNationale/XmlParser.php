<?php

namespace App\Services\AssembleeNationale;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use XMLReader;

class XmlParser
{
    protected string $sourceKey;

    protected array $sourceConfig;

    protected int $chunkSize;

    public function __construct(string $sourceKey)
    {
        $this->sourceKey = $sourceKey;
        $this->sourceConfig = config("assemblee-nationale.sources.{$sourceKey}");
        $this->chunkSize = config('assemblee-nationale.import.chunk_size', 1000);

        if (! $this->sourceConfig) {
            throw new \InvalidArgumentException("Source inconnue : {$sourceKey}");
        }
    }

    /**
     * Parse les fichiers XML et retourne un générateur d'éléments
     */
    public function parse(string $xmlPath): \Generator
    {
        $files = $this->getXmlFiles($xmlPath);

        foreach ($files as $file) {
            Log::channel('an-sync')->info("Parsing : {$file}");

            if ($this->shouldUseChunkedParsing()) {
                yield from $this->parseChunked($file);
            } else {
                yield from $this->parseSimple($file);
            }
        }
    }

    /**
     * Parse avec SimpleXML (pour les petits fichiers)
     */
    protected function parseSimple(string $filePath): \Generator
    {
        $xml = simplexml_load_file($filePath);

        if ($xml === false) {
            throw new \RuntimeException("Impossible de parser : {$filePath}");
        }

        $itemElement = $this->sourceConfig['item_element'];

        // Chercher les éléments dans la structure XML
        foreach ($this->findElements($xml, $itemElement) as $element) {
            yield $this->elementToArray($element);
        }
    }

    /**
     * Parse avec XMLReader (pour les gros fichiers - streaming)
     */
    protected function parseChunked(string $filePath): \Generator
    {
        $reader = new XMLReader;

        if (! $reader->open($filePath)) {
            throw new \RuntimeException("Impossible d'ouvrir : {$filePath}");
        }

        $itemElement = $this->sourceConfig['item_element'];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === $itemElement) {
                $xml = simplexml_load_string($reader->readOuterXml());

                if ($xml !== false) {
                    yield $this->elementToArray($xml);
                }
            }
        }

        $reader->close();
    }

    /**
     * Recherche récursive des éléments dans le XML
     */
    protected function findElements(\SimpleXMLElement $xml, string $elementName): \Generator
    {
        // Chercher directement
        if (isset($xml->{$elementName})) {
            foreach ($xml->{$elementName} as $element) {
                yield $element;
            }

            return;
        }

        // Chercher dans les enfants (un niveau)
        foreach ($xml->children() as $child) {
            if (isset($child->{$elementName})) {
                foreach ($child->{$elementName} as $element) {
                    yield $element;
                }
            }

            // Chercher aussi dans les sous-enfants (deux niveaux)
            foreach ($child->children() as $subChild) {
                if ($subChild->getName() === $elementName) {
                    yield $subChild;
                }
            }
        }
    }

    /**
     * Convertit un élément SimpleXML en tableau
     */
    protected function elementToArray(\SimpleXMLElement $element): array
    {
        $result = [];

        // Attributs
        foreach ($element->attributes() as $key => $value) {
            $result['@'.$key] = (string) $value;
        }

        // Enfants
        foreach ($element->children() as $child) {
            $name = $child->getName();
            $value = $this->parseChild($child);

            // Gérer les éléments multiples
            if (isset($result[$name])) {
                if (! is_array($result[$name]) || ! isset($result[$name][0])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }

        // Si l'élément n'a pas d'enfants, retourner sa valeur textuelle
        if (empty($result) && (string) $element !== '') {
            return (string) $element;
        }

        return $result;
    }

    /**
     * Parse un élément enfant
     */
    protected function parseChild(\SimpleXMLElement $child): mixed
    {
        $children = $child->children();

        if (count($children) === 0) {
            // Élément simple
            $value = (string) $child;

            // Vérifier les attributs
            $attributes = $child->attributes();
            if (count($attributes) > 0) {
                $result = ['#text' => $value];
                foreach ($attributes as $key => $attrValue) {
                    $result['@'.$key] = (string) $attrValue;
                }

                return $result;
            }

            return $value;
        }

        // Élément avec enfants
        return $this->elementToArray($child);
    }

    /**
     * Retourne les fichiers XML à parser (recherche récursive)
     */
    protected function getXmlFiles(string $xmlPath): array
    {
        if (! File::isDirectory($xmlPath)) {
            throw new \RuntimeException("Répertoire introuvable : {$xmlPath}");
        }

        // Recherche récursive de tous les fichiers .xml
        $files = File::allFiles($xmlPath);
        $xmlFiles = [];

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) === 'xml') {
                $xmlFiles[] = $file->getPathname();
            }
        }

        if (empty($xmlFiles)) {
            throw new \RuntimeException("Aucun fichier XML trouvé dans : {$xmlPath}");
        }

        return $xmlFiles;
    }

    /**
     * Détermine si on doit utiliser le parsing chunked
     */
    protected function shouldUseChunkedParsing(): bool
    {
        return $this->sourceConfig['chunked'] ?? false;
    }

    /**
     * Compte le nombre d'éléments sans tout charger en mémoire
     */
    public function count(string $xmlPath): int
    {
        $count = 0;
        $files = $this->getXmlFiles($xmlPath);

        foreach ($files as $file) {
            $reader = new XMLReader;
            if ($reader->open($file)) {
                $itemElement = $this->sourceConfig['item_element'];

                while ($reader->read()) {
                    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === $itemElement) {
                        $count++;
                        $reader->next(); // Sauter le contenu de l'élément
                    }
                }

                $reader->close();
            }
        }

        return $count;
    }

    /**
     * Retourne un échantillon d'éléments pour inspection
     */
    public function sample(string $xmlPath, int $limit = 5): array
    {
        $samples = [];
        $count = 0;

        foreach ($this->parse($xmlPath) as $element) {
            $samples[] = $element;
            $count++;

            if ($count >= $limit) {
                break;
            }
        }

        return $samples;
    }
}
