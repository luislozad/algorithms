<?php

class Mapper
{
    private array $list = [];

    public function __construct(array $list)
    {
        $this->list = array_flip($list);
    }

    public function has(int|string $key)
    {
        return isset($this->list[$key]);
    }
}

$selfClosingTags = new Mapper([
    "area",
    "base",
    "br",
    "col",
    "embed",
    "hr",
    "img",
    "input",
    "link",
    "meta",
    "param",
    "source",
    "track",
    "wbr"
]);

class Node
{
    public $openTag;
    public $raw;
    public $isClosed;
    public $children;
    public $parent;

    public function __construct($openTag, $parent = null)
    {
        $this->openTag = $openTag;
        $this->raw = '';
        $this->isClosed = false;
        $this->children = [];
        $this->parent = $parent;
    }
}

function findFirstTag($html, $start = 0)
{
    $openTagIndex = strpos($html, '<', $start);

    if ($openTagIndex === false) return null;

    $closeTagIndex = strpos($html, '>', $openTagIndex);

    if ($closeTagIndex === false) return null;

    $isClosingTag = $html[$openTagIndex + 1] === '/';

    $startIndex = $openTagIndex + ($isClosingTag ? 2 : 1);

    $tagContent = substr($html, $startIndex, $closeTagIndex - $startIndex);

    $tagName = preg_split('/\s/', $tagContent)[0];

    return [
        'isClosing' => $isClosingTag,
        'tagName' => $tagName,
        'start' => $openTagIndex,
        'end' => $closeTagIndex + 1
    ];
}

function cleanHTML($html)
{
    $html = preg_replace('/<!--[\s\S]*?-->/', '', $html);

    $html = preg_replace('/>\s+</', '><', $html);

    return trim($html);
}

function buildTreeHtml($html)
{
    global $selfClosingTags;

    $html = cleanHTML($html);
    $root = new Node('root');
    $currentNode = $root;
    $start = 0;

    while ($start < strlen($html)) {
        $tag = findFirstTag($html, $start);
        if (!$tag) break;

        if ($tag['isClosing']) {
            if ($currentNode->openTag === $tag['tagName']) {
                $currentNode->isClosed = true;
                $currentNode = $currentNode->parent ?? $root;
            } elseif ($currentNode->openTag !== 'root') {
                $currentNode = $currentNode->parent ?? $root;
                continue;
            } else {
                break;
            }
        } else {
            $newNode = new Node($tag['tagName'], $currentNode);
            $newNode->raw = substr($html, $tag['start'], $tag['end'] - $tag['start']);
            $currentNode->children[] = $newNode;

            if (!$selfClosingTags->has($tag['tagName'])) {
                $currentNode = $newNode;
            }
        }

        $start = $tag['end'];
    }

    return $root;
}
