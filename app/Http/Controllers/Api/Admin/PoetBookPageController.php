<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoetBook;
use App\Models\PoetBookPage;
use App\Models\Poetry;
use App\Models\Couplets;
use Illuminate\Http\Request;

class PoetBookPageController extends Controller
{
    /**
     * Get all pages for a book, syncing if necessary.
     */
    public function index($bookId)
    {
        $book = PoetBook::findOrFail($bookId);

        // Ensure all pages exist in the poet_book_pages table
        $this->ensurePagesExist($book);

        $pages = PoetBookPage::where('book_id', $bookId)
            ->orderBy('page_number', 'asc')
            ->get();

        return response()->json([
            'book' => $book,
            'pages' => $pages
        ]);
    }

    /**
     * Update a single page.
     */
    public function update(Request $request, $bookId, $pageId)
    {
        $page = PoetBookPage::where('book_id', $bookId)->findOrFail($pageId);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|string|in:poetry,information,cover,preface,blank',
            'is_completed' => 'required|boolean',
        ]);

        $page->update($validated);

        return response()->json($page);
    }

    /**
     * Batch update pages.
     */
    public function batchUpdate(Request $request, $bookId)
    {
        $validated = $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'exists:poet_book_pages,id',
            'type' => 'nullable|string|in:poetry,information,cover,preface,blank',
            'is_completed' => 'nullable|boolean',
        ]);

        $updateData = array_filter([
            'type' => $validated['type'] ?? null,
            'is_completed' => $validated['is_completed'] ?? null,
        ], fn($v) => !is_null($v));

        if (!empty($updateData)) {
            PoetBookPage::whereIn('id', $validated['page_ids'])
                ->where('book_id', $bookId)
                ->update($updateData);
        }

        return response()->json(['message' => 'Pages updated successfully']);
    }

    /**
     * Sync poetry/couplets with pages table.
     */
    public function sync($bookId)
    {
        $book = PoetBook::findOrFail($bookId);
        $this->ensurePagesExist($book);

        // Get all pages that have poetry
        $poetryPages = Poetry::where('book_id', $bookId)
            ->get()
            ->flatMap(fn($p) => range($p->page_start, $p->page_end))
            ->unique();

        $coupletPages = Couplets::where('book_id', $bookId)
            ->get()
            ->flatMap(fn($c) => range($c->page_start, $c->page_end))
            ->unique();

        $allDigitizedPages = $poetryPages->concat($coupletPages)->unique();

        // Mark these as completed and type 'poetry'
        if ($allDigitizedPages->isNotEmpty()) {
            PoetBookPage::where('book_id', $bookId)
                ->whereIn('page_number', $allDigitizedPages)
                ->update([
                    'is_completed' => true,
                    'type' => 'poetry'
                ]);
        }

        return response()->json(['message' => 'Sync completed']);
    }

    /**
     * Helper to ensure all pages from 1 to total_pages exist in the table.
     */
    private function ensurePagesExist(PoetBook $book)
    {
        $existingPageNumbers = PoetBookPage::where('book_id', $book->id)
            ->pluck('page_number')
            ->toArray();

        $missingPages = [];
        for ($i = 1; $i <= $book->total_pages; $i++) {
            if (!in_array($i, $existingPageNumbers)) {
                $missingPages[] = [
                    'book_id' => $book->id,
                    'page_number' => $i,
                    'type' => 'poetry',
                    'is_completed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($missingPages)) {
            // Chunk inserts for large books
            foreach (array_chunk($missingPages, 100) as $chunk) {
                PoetBookPage::insert($chunk);
            }
        }
    }
}
