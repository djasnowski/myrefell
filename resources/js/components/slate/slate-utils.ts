import { type Descendant } from "slate";

export const EMPTY_EDITOR_VALUE: Descendant[] = [{ type: "paragraph", children: [{ text: "" }] }];

/**
 * Serialize Slate JSON to plain text.
 */
export function serializeToPlainText(nodes: Descendant[]): string {
    return nodes.map((n) => serializeNode(n)).join("\n");
}

function serializeNode(node: Descendant): string {
    if ("text" in node) {
        return node.text;
    }

    const children = node.children.map((child: Descendant) => serializeNode(child)).join("");

    switch (node.type) {
        case "bulleted-list":
        case "numbered-list":
            return children;
        case "list-item":
            return `- ${children}`;
        case "block-quote":
            return `> ${children}`;
        case "heading-one":
        case "heading-two":
        case "heading":
            return children;
        default:
            return children;
    }
}

/**
 * Check if Slate content is effectively empty.
 */
export function isEmptyContent(nodes: Descendant[]): boolean {
    if (!nodes || nodes.length === 0) {
        return true;
    }

    return serializeToPlainText(nodes).trim().length === 0;
}
