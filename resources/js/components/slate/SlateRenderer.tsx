import type { Descendant } from "slate";

interface SlateRendererProps {
    content: Descendant[];
}

export default function SlateRenderer({ content }: SlateRendererProps) {
    if (!content || !Array.isArray(content)) {
        return null;
    }

    return (
        <div className="prose-medieval">
            {content.map((node, i) => (
                <RenderElement key={i} element={node} />
            ))}
        </div>
    );
}

function RenderElement({ element }: { element: any }) {
    if ("text" in element) {
        return <RenderLeaf leaf={element} />;
    }

    const children = element.children?.map((child: any, i: number) => {
        if ("text" in child) {
            return <RenderLeaf key={i} leaf={child} />;
        }
        return <RenderElement key={i} element={child} />;
    });

    const style: React.CSSProperties = {};
    if (element.align) {
        style.textAlign = element.align;
    }

    switch (element.type) {
        case "heading-one":
            return (
                <h1 style={style} className="mb-2 font-pixel text-xl font-bold text-amber-200">
                    {children}
                </h1>
            );
        case "heading-two":
            return (
                <h2 style={style} className="mb-2 font-pixel text-lg font-bold text-amber-200">
                    {children}
                </h2>
            );
        case "heading":
            return (
                <h2 style={style} className="mb-2 font-pixel text-lg font-bold text-amber-200">
                    {children}
                </h2>
            );
        case "block-quote":
            return (
                <blockquote
                    style={style}
                    className="mb-2 border-l-2 border-amber-600/50 pl-3 italic text-stone-400"
                >
                    {children}
                </blockquote>
            );
        case "bulleted-list":
            return (
                <ul style={style} className="mb-2 ml-5 list-disc text-stone-300">
                    {children}
                </ul>
            );
        case "numbered-list":
            return (
                <ol style={style} className="mb-2 ml-5 list-decimal text-stone-300">
                    {children}
                </ol>
            );
        case "list-item":
            return (
                <li style={style} className="mb-0.5">
                    {children}
                </li>
            );
        default:
            return (
                <p style={style} className="mb-1 text-stone-300">
                    {children}
                </p>
            );
    }
}

function RenderLeaf({ leaf }: { leaf: any }) {
    let content: React.ReactNode = leaf.text;

    if (!content) {
        return null;
    }

    if (leaf.bold) {
        content = <strong>{content}</strong>;
    }
    if (leaf.italic) {
        content = <em>{content}</em>;
    }
    if (leaf.underline) {
        content = <u>{content}</u>;
    }
    if (leaf.code) {
        content = (
            <code className="rounded bg-stone-700/50 px-1 py-0.5 font-mono text-xs text-amber-300">
                {content}
            </code>
        );
    }

    return <>{content}</>;
}
