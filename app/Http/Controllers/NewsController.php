<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');

        $allowedSorts = ['id', 'title', 'event_date', 'created_at', 'created_by'];

        $query = News::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('created_by', 'like', "%{$search}%");
            });
        }

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $news = $query->paginate(20)->appends($request->query());

        return view('news.index', compact('news', 'search', 'sortBy', 'sortDir'));
    }

    public function create(): View
    {
        return view('news.create');
    }

    public function edit(News $news): View
    {
        return view('news.edit', compact('news'));
    }
}
