const selfClosingTags = new Set([
    "area", "base", "br", "col", "embed", "hr", "img", "input",
    "link", "meta", "param", "source", "track", "wbr"
]);

class Node {
    constructor(openTag, parent = null) {
        this.openTag = openTag;
        this.raw = '';
        this.isClosed = false;
        this.children = [];
        this.parent = parent;
    }
}

function findFirstTag(html, start = 0) {
    const openTagIndex = html.indexOf('<', start);
    if (openTagIndex === -1) return null;

    const closeTagIndex = html.indexOf('>', openTagIndex);
    if (closeTagIndex === -1) return null;

    const isClosingTag = html[openTagIndex + 1] === '/';
    const tagContent = html.slice(openTagIndex + (isClosingTag ? 2 : 1), closeTagIndex);
    const tagName = tagContent.split(/\s/)[0];

    return {
        isClosing: isClosingTag,
        tagName,
        start: openTagIndex,
        end: closeTagIndex + 1
    };
}

function cleanHTML(html) {
    return html.replace(/<!--[\s\S]*?-->/g, '')
               .replace(/>\s+</g, '><')
               .trim();
}

function buildTreeHtml(html) {
    html = cleanHTML(html);
    const root = new Node('root');
    let currentNode = root;
    let start = 0;

    while (start < html.length) {
        const tag = findFirstTag(html, start);
        if (!tag) break;

        if (tag.isClosing) {
            if (currentNode.openTag === tag.tagName) {
                currentNode.isClosed = true;
                currentNode = currentNode.parent || root;
            } else if(currentNode.openTag !== 'root') {
		        currentNode = currentNode.parent || root;
		        continue;
	         } else {
		        break;
	        }
        } else {
            const newNode = new Node(tag.tagName, currentNode);
            newNode.raw = html.slice(tag.start, tag.end);
            currentNode.children.push(newNode);

            if (!selfClosingTags.has(tag.tagName)) {
                currentNode = newNode;
            }
        }

        start = tag.end;
    }

    return root;
}