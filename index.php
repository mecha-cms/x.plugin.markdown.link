<?php namespace x\markdown__link\page;

function content($content) {
    if (!$content || false === \strpos($content, '[link:')) {
        return $content;
    }
    if (!$path = $this->path) {
        return $content;
    }
    $type = $this->type;
    if ('Markdown' !== $type && 'text/markdown' !== $type) {
        return $content;
    }
    \extract($GLOBALS, \EXTR_SKIP);
    return \preg_replace_callback('/(?:\[([^]]*)\])?\[link:((?:\.{2}\/)*|\.{2})([^\s?&#]*)([?&#].*?)?\]/', static function ($m) use ($path, $url) {
        $route = \rtrim(\strtr(\dirname($path) . \D, [
            \LOT . \D . 'page' . \D => ""
        ]), \D);
        if (!empty($m[2])) {
            if ('..' === $m[2] && empty($m[3])) {
                $route = \dirname($route);
                $m[2] = "";
            } else if (0 !== ($deep = \substr_count($m[2], '../'))) {
                $route = \dirname($route, $deep);
                $m[2] = \strtr($m[2], [
                    '../' => ""
                ]);
            }
        }
        $route = "" === $route || '.' === $route ? "" : '/' . $route;
        if (empty($m[2]) && 0 === \strpos($m[3], '/')) {
            $folder = \LOT . \D . 'page' . \strtr($m[3], '/', \D);
        } else {
            $folder = \LOT . \D . 'page' . \strtr($route . \D . $m[3], '/', D);
        }
        // Fix case for link(s) that point to a page with pagination offset such as `[link:store/extension/1]`
        if (\preg_match('/\/([1-9]\d*)$/', $folder) && \is_dir($d = \dirname($folder))) {
            $folder = $d;
        }
        $m[4] = $m[4] ?? ""; // This is the query string and hash
        $file = \exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1);
        if ($m[3] && !$file) {
            return '<s role="status" style="color: #f00;" title="' . ($m[1] ? \i('broken link') : $m[0]) . '">' . ($m[1] ?: \i('broken link')) . '</s>';
        }
        $page = new \Page($file);
        $title = $page->title ?? \To::title(\basename($m[2]));
        $v = \strtr($m[3] ? $url . (0 === \strpos($m[3], '/') ? $m[3] : $route . '/' . $m[3]) . $m[4] . ' "' . \To::text($title) . '"' : $url . $route . $m[4], \D, '/');
        return '[' . ($m[1] ?: $title) . '](' . $v . ')';
    }, $content);
}

function description($description) {
    return \fire(__NAMESPACE__ . "\\content", [$description], $this);
}

// Make sure to run before `x\markdown\page\*` hook
\Hook::set('page.content', __NAMESPACE__ . "\\content", 1.9);
\Hook::set('page.description', __NAMESPACE__ . "\\description", 1.9);