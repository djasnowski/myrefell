<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Item::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('type', 'ilike', "%{$search}%")
                    ->orWhere('subtype', 'ilike', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by subtype
        if ($subtype = $request->input('subtype')) {
            $query->where('subtype', $subtype);
        }

        // Filter by rarity
        if ($rarity = $request->input('rarity')) {
            $query->where('rarity', $rarity);
        }

        $items = $query->orderBy('type')
            ->orderBy('subtype')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        // Get unique types, subtypes, and rarities for filters
        $types = Item::distinct()->pluck('type')->filter()->sort()->values();
        $subtypes = Item::distinct()->pluck('subtype')->filter()->sort()->values();
        $rarities = Item::distinct()->pluck('rarity')->filter()->sort()->values();

        return Inertia::render('Admin/Items/Index', [
            'items' => $items,
            'types' => $types,
            'subtypes' => $subtypes,
            'rarities' => $rarities,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'subtype' => $request->input('subtype', ''),
                'rarity' => $request->input('rarity', ''),
            ],
        ]);
    }
}
