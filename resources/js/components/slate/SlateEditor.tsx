import { useCallback, useMemo, useRef } from "react";
import {
    createEditor,
    type Descendant,
    Editor,
    Node,
    Element as SlateElement,
    Transforms,
} from "slate";
import { Editable, ReactEditor, Slate, useSlate, withReact } from "slate-react";
import { withHistory } from "slate-history";
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code,
    Heading1,
    Heading2,
    Italic,
    List,
    ListOrdered,
    Quote,
    Underline,
} from "lucide-react";

const LIST_TYPES = ["numbered-list", "bulleted-list"];
const TEXT_ALIGN_TYPES = ["left", "center", "right", "justify"];

interface SlateEditorProps {
    value: Descendant[];
    onChange: (value: Descendant[]) => void;
    placeholder?: string;
    minHeight?: string;
}

export default function SlateEditor({ value, onChange, placeholder, minHeight }: SlateEditorProps) {
    const editor = useMemo(() => withHistory(withReact(createEditor())), []);
    const editableRef = useRef<HTMLDivElement>(null);

    const renderElement = useCallback((props: any) => <Element {...props} />, []);
    const renderLeaf = useCallback((props: any) => <Leaf {...props} />, []);

    const handleKeyDown = useCallback(
        (event: React.KeyboardEvent) => {
            if (!event.ctrlKey && !event.metaKey) {
                return;
            }

            const hotkeys: Record<string, string> = {
                b: "bold",
                i: "italic",
                u: "underline",
                "`": "code",
            };

            const mark = hotkeys[event.key];
            if (mark) {
                event.preventDefault();
                toggleMark(editor, mark);
            }
        },
        [editor],
    );

    const handleWrapperClick = useCallback(
        (e: React.MouseEvent<HTMLDivElement>) => {
            if (e.target === e.currentTarget) {
                ReactEditor.focus(editor);
                Transforms.select(editor, Editor.end(editor, []));
            }
        },
        [editor],
    );

    return (
        <Slate editor={editor} initialValue={value} onChange={onChange}>
            <div className="rounded-lg border border-stone-600/50 bg-stone-900/50">
                <Toolbar />
                <div
                    className={`cursor-text px-3 py-2 ${minHeight || "min-h-[200px]"}`}
                    onClick={handleWrapperClick}
                >
                    <Editable
                        ref={editableRef}
                        renderElement={renderElement}
                        renderLeaf={renderLeaf}
                        onKeyDown={handleKeyDown}
                        placeholder={placeholder || "Write your broadsheet..."}
                        className="h-full text-sm text-stone-200 outline-none placeholder:text-stone-500"
                        style={{ minHeight: "100%" }}
                        spellCheck
                    />
                </div>
            </div>
        </Slate>
    );
}

function Toolbar() {
    return (
        <div className="flex flex-wrap items-center gap-0.5 border-b border-stone-700/50 px-2 py-1">
            <MarkButton format="bold" icon={Bold} />
            <MarkButton format="italic" icon={Italic} />
            <MarkButton format="underline" icon={Underline} />
            <MarkButton format="code" icon={Code} />
            <div className="mx-1 h-4 w-px bg-stone-700" />
            <BlockButton format="heading-one" icon={Heading1} />
            <BlockButton format="heading-two" icon={Heading2} />
            <BlockButton format="block-quote" icon={Quote} />
            <div className="mx-1 h-4 w-px bg-stone-700" />
            <BlockButton format="bulleted-list" icon={List} />
            <BlockButton format="numbered-list" icon={ListOrdered} />
            <div className="mx-1 h-4 w-px bg-stone-700" />
            <BlockButton format="left" icon={AlignLeft} />
            <BlockButton format="center" icon={AlignCenter} />
            <BlockButton format="right" icon={AlignRight} />
            <BlockButton format="justify" icon={AlignJustify} />
        </div>
    );
}

function MarkButton({ format, icon: Icon }: { format: string; icon: any }) {
    const editor = useSlate();
    const isActive = isMarkActive(editor, format);

    return (
        <button
            type="button"
            onMouseDown={(e) => {
                e.preventDefault();
                toggleMark(editor, format);
            }}
            className={`rounded p-1.5 transition ${
                isActive
                    ? "bg-amber-800/50 text-amber-300"
                    : "text-stone-400 hover:bg-stone-700/50 hover:text-stone-200"
            }`}
        >
            <Icon className="h-4 w-4" />
        </button>
    );
}

function BlockButton({ format, icon: Icon }: { format: string; icon: any }) {
    const editor = useSlate();
    const isAlignFormat = TEXT_ALIGN_TYPES.includes(format);
    const isActive = isBlockActive(editor, format, isAlignFormat ? "align" : "type");

    return (
        <button
            type="button"
            onMouseDown={(e) => {
                e.preventDefault();
                toggleBlock(editor, format);
            }}
            className={`rounded p-1.5 transition ${
                isActive
                    ? "bg-amber-800/50 text-amber-300"
                    : "text-stone-400 hover:bg-stone-700/50 hover:text-stone-200"
            }`}
        >
            <Icon className="h-4 w-4" />
        </button>
    );
}

function isMarkActive(editor: Editor, format: string): boolean {
    const marks = Editor.marks(editor);
    return marks ? (marks as any)[format] === true : false;
}

function toggleMark(editor: Editor, format: string) {
    const isActive = isMarkActive(editor, format);
    if (isActive) {
        Editor.removeMark(editor, format);
    } else {
        Editor.addMark(editor, format, true);
    }
}

function isBlockActive(
    editor: Editor,
    format: string,
    blockType: "type" | "align" = "type",
): boolean {
    const { selection } = editor;
    if (!selection) {
        return false;
    }

    const [match] = Array.from(
        Editor.nodes(editor, {
            at: Editor.unhangRange(editor, selection),
            match: (n) => {
                if (!Editor.isEditor(n) && SlateElement.isElement(n)) {
                    if (blockType === "align") {
                        return (n as any).align === format;
                    }
                    return (n as any).type === format;
                }
                return false;
            },
        }),
    );

    return !!match;
}

function toggleBlock(editor: Editor, format: string) {
    const isAlignFormat = TEXT_ALIGN_TYPES.includes(format);
    const isActive = isBlockActive(editor, format, isAlignFormat ? "align" : "type");
    const isList = LIST_TYPES.includes(format);

    Transforms.unwrapNodes(editor, {
        match: (n) =>
            !Editor.isEditor(n) &&
            Node.isElement(n) &&
            LIST_TYPES.includes((n as any).type) &&
            !isAlignFormat,
        split: true,
    });

    if (isAlignFormat) {
        Transforms.setNodes<SlateElement>(editor, {
            align: isActive ? undefined : format,
        } as any);
    } else {
        const newType = isActive ? "paragraph" : isList ? "list-item" : format;
        Transforms.setNodes<SlateElement>(editor, { type: newType } as any);

        if (!isActive && isList) {
            const block = { type: format, children: [] } as any;
            Transforms.wrapNodes(editor, block);
        }
    }
}

function Element({ attributes, children, element }: any) {
    const style: React.CSSProperties = {};
    if (element.align) {
        style.textAlign = element.align;
    }

    switch (element.type) {
        case "heading-one":
            return (
                <h1
                    {...attributes}
                    style={style}
                    className="mb-2 font-pixel text-xl font-bold text-amber-200"
                >
                    {children}
                </h1>
            );
        case "heading-two":
            return (
                <h2
                    {...attributes}
                    style={style}
                    className="mb-2 font-pixel text-lg font-bold text-amber-200"
                >
                    {children}
                </h2>
            );
        case "block-quote":
            return (
                <blockquote
                    {...attributes}
                    style={style}
                    className="mb-2 border-l-2 border-amber-600/50 pl-3 italic text-stone-400"
                >
                    {children}
                </blockquote>
            );
        case "bulleted-list":
            return (
                <ul {...attributes} style={style} className="mb-2 ml-5 list-disc text-stone-300">
                    {children}
                </ul>
            );
        case "numbered-list":
            return (
                <ol {...attributes} style={style} className="mb-2 ml-5 list-decimal text-stone-300">
                    {children}
                </ol>
            );
        case "list-item":
            return (
                <li {...attributes} style={style} className="mb-0.5">
                    {children}
                </li>
            );
        default:
            return (
                <p {...attributes} style={style} className="mb-1 text-stone-300">
                    {children}
                </p>
            );
    }
}

function Leaf({ attributes, children, leaf }: any) {
    if (leaf.bold) {
        children = <strong>{children}</strong>;
    }
    if (leaf.italic) {
        children = <em>{children}</em>;
    }
    if (leaf.underline) {
        children = <u>{children}</u>;
    }
    if (leaf.code) {
        children = (
            <code className="rounded bg-stone-700/50 px-1 py-0.5 font-mono text-xs text-amber-300">
                {children}
            </code>
        );
    }

    return <span {...attributes}>{children}</span>;
}
