import { Backpack, ChevronDown } from "lucide-react";
import { useEffect, useState } from "react";

const STORAGE_KEY = "inventory-summary-expanded";

interface InventoryItem {
    name: string;
    quantity: number;
}

export function InventorySummary({ items }: { items: InventoryItem[] }) {
    const [expanded, setExpanded] = useState(() => {
        try {
            return localStorage.getItem(STORAGE_KEY) !== "false";
        } catch {
            return true;
        }
    });

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_KEY, String(expanded));
        } catch {}
    }, [expanded]);

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="mb-4 rounded-lg border border-stone-700 bg-stone-800/50">
            <button
                onClick={() => setExpanded(!expanded)}
                className="flex w-full items-center gap-2 px-3 py-2 text-left"
            >
                <Backpack className="h-3.5 w-3.5 text-amber-400" />
                <span className="font-pixel text-[10px] text-amber-300">My Materials</span>
                <span className="font-pixel text-[10px] text-stone-500">({items.length})</span>
                <ChevronDown
                    className={`ml-auto h-3.5 w-3.5 text-stone-500 transition-transform ${expanded ? "rotate-180" : ""}`}
                />
            </button>
            {expanded && (
                <div className="flex flex-wrap gap-x-3 gap-y-1 border-t border-stone-700/50 px-3 py-2">
                    {items.map((item) => (
                        <span key={item.name} className="font-pixel text-[10px] text-stone-300">
                            {item.name} <span className="text-amber-400">×{item.quantity}</span>
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
}
