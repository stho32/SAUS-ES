<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicNewsController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $query = News::query()->orderBy('event_date', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $news = $query->paginate(10)->appends($request->query());

        return view('public.news.index', compact('news', 'search'));
    }

    public function show(News $news): View
    {
        return view('public.news.show', compact('news'));
    }
}
