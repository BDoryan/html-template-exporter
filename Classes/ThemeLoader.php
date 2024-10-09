<?php

namespace Classes;

class ThemeLoader
{

    private string $dir;
    private array $files;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    private function isViewFile($file)
    {
        return preg_match('/\.(html|php|twig|tpl)$/', $file);
    }

    /**
     * Fonction pour extraire tous les blocs enfants du body, basés sur les commentaires qui les précèdent.
     */
    private function extractBlocksFromBody($html): array
    {
        $domDocument = new \DOMDocument();

        // Because DOMDocument broke the encoding
        $utf8 = "<?xml encoding='utf-8' ?>";
        $domDocument->loadHTML($utf8 . $html, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $body = $domDocument->getElementsByTagName('body')->item(0);

        $childNodes = $body->childNodes;

        // Comments end with 'end' are ignored
        $nodeIgnored = [];

        $blocks = [];
        foreach ($body->childNodes as $index => $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $comment = $node->nodeValue;
                $comment = trim($comment);

                if (in_array($comment, $nodeIgnored))
                    continue;

                $blocks_finds = [];

                // Contain in comment the word 'start'
                if ($index + 1 < count($childNodes)) {
                    for ($i = $index + 1; $i < count($childNodes); $i++) {
                        $subNode = $childNodes[$i];

                        $html = $domDocument->saveHTML($subNode);
                        if (trim($html) === '')
                            continue;

                        if ($subNode->nodeType === XML_COMMENT_NODE) {
                            $another_comment = $subNode->nodeValue;
                            $another_comment = trim($another_comment);

                            if (strpos(strtolower($another_comment), 'end') !== false) {
                                $nodeIgnored[] = $another_comment;
                                break;
                            }
                            break;
                        } else {
                            $html = $domDocument->saveHTML($subNode);
                            $blocks_finds[] = $html;
                        }
                    }
                }

                $blocks[$comment] = $blocks_finds;
            }
        }


        return $blocks;
    }

    public function extractDataFromFile($filepath)
    {
        $file = basename($filepath);
        global $logger;

        $logger->info("Extracting data from file {$file}...");

        if ($this->isViewFile($file)) {
            $logger->info("{$file} is a view file.");

            $filename = pathinfo($file, PATHINFO_FILENAME);

            $content = file_get_contents($filepath);
            $blocks = $this->extractBlocksFromBody($content);

            $logger->info("Extracted " . count($blocks) . " blocks from {$file}.");
            $this->files[$filename] = $blocks;
        } else {
            $logger->info("{$file} is not a view file.");
        }
    }

    public function extractDataFromDir($dir)
    {
        global $logger;

        $logger->info("Extracting data from directory {$dir}...");
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..')
                continue;

            $filepath = "{$dir}/{$file}";

            if (is_file($filepath)) {
                $this->extractDataFromFile($filepath);
            } else if (is_dir($filepath)) {
                $directory = "{$dir}/{$file}";
                $this->extractDataFromDir($directory);
            }
        }
    }

    public function extractData()
    {
        $this->extractDataFromDir($this->dir);
    }

    public function exportData($outDir)
    {
        global $logger;

        $logger->info("Exporting data to directory {$outDir}...");
        // Create blocks file
        foreach ($this->files as $filename => $blocks) {
            $logger->info("Exporting data for {$filename}...");
            $blocksFile = "{$outDir}/{$filename}.json";
            file_put_contents($blocksFile, json_encode($blocks, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Return the directory of the theme
     *
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }
}