<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseSection;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStockItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    /**
     * Display the warehouse monitor with grid and stock.
     */
    public function show(Warehouse $warehouse)
    {
        $user = auth()->user();

        if ($user->role !== 'CEO' && $warehouse->supervisor_id !== $user->id && $warehouse->manager_id !== $user->id) {
            abort(403, 'You do not have access to this warehouse.');
        }

        // Fetch sections with shelves AND items assigned directly to the section (no shelf)
        $sections = $warehouse->sections()
            ->with(['shelves.stockItems.material', 'stockItemsNoShelf.material'])
            ->get();

        // Fetch stock that has no location assigned yet
        $unassignedStock = WarehouseStockItem::where('warehouse_id', $warehouse->id)
            ->whereNull('shelf_id')
            ->whereNull('section_id')
            ->with('material')
            ->get();

        return Inertia::render('Dashboard/Warehouse/Monitor', [
            'warehouse' => $warehouse,
            'sections' => $sections,
            'unassignedStock' => $unassignedStock,
        ]);
    }

    /**
     * Update grid layout and dimensions.
     * Uses a Sync approach to prevent data loss for existing stock.
     */
    public function updateLayout(Request $request, Warehouse $warehouse)
    {
        $user = auth()->user();
        if ($user->role !== 'CEO' && $warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'grid_rows' => 'required|integer',
            'grid_cols' => 'required|integer',
            'sections' => 'array',
            'sections.*.id' => 'nullable',
            'sections.*.name' => 'required|string',
            'sections.*.row' => 'required|integer',
            'sections.*.col' => 'required|integer',
            'sections.*.shelves' => 'array',
        ]);

        try {
            DB::beginTransaction();

            $warehouse->update([
                'grid_rows' => $data['grid_rows'],
                'grid_cols' => $data['grid_cols'],
            ]);

            $activeSectionIds = [];

            foreach ($data['sections'] as $sec) {
                // If ID is null or starts with 'temp', create new, otherwise update
                $sectionId = (isset($sec['id']) && !str_starts_with($sec['id'], 'temp-')) ? $sec['id'] : null;

                $section = WarehouseSection::updateOrCreate(
                    ['id' => $sectionId, 'warehouse_id' => $warehouse->id],
                    [
                        'name' => $sec['name'],
                        'grid_row' => $sec['row'],
                        'grid_col' => $sec['col'],
                    ]
                );
                $activeSectionIds[] = $section->id;

                $activeShelfIds = [];
                if (isset($sec['shelves'])) {
                    foreach ($sec['shelves'] as $sh) {
                        // Check if shelf is existing or new (ts- prefix)
                        $shId = (isset($sh['id']) && !str_starts_with($sh['id'], 'ts-')) ? $sh['id'] : null;

                        $shelf = WarehouseShelf::updateOrCreate(
                            ['id' => $shId, 'section_id' => $section->id],
                            ['shelf_number' => $sh['shelf_number']]
                        );
                        $activeShelfIds[] = $shelf->id;
                    }
                }
                // Delete shelves that were removed in the UI
                $section->shelves()->whereNotIn('id', $activeShelfIds)->delete();
            }

            // Delete sections removed in UI
            $warehouse->sections()->whereNotIn('id', $activeSectionIds)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse layout and shelves saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Layout error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Creation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign stock to either a specific shelf OR just a section box.
     */
    public function assignToShelf(Request $request)
    {
        $data = $request->validate([
            'stock_item_id' => 'required|exists:warehouse_stock_items,id',
            'shelf_id'      => 'nullable|exists:warehouse_shelves,id',
            'section_id'    => 'nullable|exists:warehouse_sections,id',
        ]);

        $stock = WarehouseStockItem::findOrFail($data['stock_item_id']);
        $user = auth()->user();

        if ($user->role !== 'CEO' && $stock->warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to assign stock.');
        }

        if (!empty($data['shelf_id'])) {
            // If dropped on a specific shelf
            $shelf = WarehouseShelf::findOrFail($data['shelf_id']);
            $stock->update([
                'shelf_id' => $shelf->id,
                'section_id' => $shelf->section_id
            ]);
        } elseif (!empty($data['section_id'])) {
            // If dropped on the general area of a section
            $stock->update([
                'section_id' => $data['section_id'],
                'shelf_id' => null
            ]);
        }

        return redirect()->back()->with('success', 'Material location updated.');
    }

    /**
     * Consume material and release it to the production floor.
     */
    public function useMaterial(Request $request, WarehouseStockItem $stockItem)
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $stockItem->quantity,
            'manufacturing_department' => 'required|string|in:knitting,dyeing,maintenance,packaging',
        ]);

        $user = auth()->user();
        if ($user->role !== 'CEO' && $stockItem->warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($data['quantity'] >= $stockItem->quantity) {
            // Full consumption
            $stockItem->update([
                'status' => 'used',
                'quantity' => 0,
                'shelf_id' => null,
                'section_id' => null
            ]);
        } else {
            // Partial consumption
            $stockItem->decrement('quantity', $data['quantity']);
        }

        return redirect()->back()->with('success', "{$data['quantity']} units released to {$data['manufacturing_department']}.");
    }
}
