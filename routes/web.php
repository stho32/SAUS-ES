<?php

use App\Http\Controllers\Api\AttachmentApiController;
use App\Http\Controllers\Api\CommentApiController;
use App\Http\Controllers\Api\ContactPersonApiController;
use App\Http\Controllers\Api\NewsApiController;
use App\Http\Controllers\Api\TicketApiController;
use App\Http\Controllers\Api\VoteApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactPersonController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ImageViewController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PublicNewsController;
use App\Http\Controllers\PublicTicketController;
use App\Http\Controllers\SausNewsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WebsiteViewController;
use Illuminate\Support\Facades\Route;

// Auth routes (no middleware)
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::get('/error', [AuthController::class, 'error'])->name('error');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Username entry (requires master_link but not username)
Route::middleware('master_link')->group(function () {
    Route::get('/username', [AuthController::class, 'usernameForm'])->name('username.form');
    Route::post('/username', [AuthController::class, 'setUsername'])->name('username.store');
});

// Protected routes (require master_link + username)
Route::middleware(['master_link', 'ensure_username'])->group(function () {
    // Dashboard / Ticket list
    Route::get('/', [TicketController::class, 'index'])->name('tickets.index');

    // Ticket CRUD
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::get('/tickets/{ticket}/email', [TicketController::class, 'email'])->name('tickets.email');

    // Special views
    Route::get('/follow-up', [FollowUpController::class, 'index'])->name('follow-up.index');
    Route::get('/website-view', [WebsiteViewController::class, 'index'])->name('website-view.index');
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/saus-news', [SausNewsController::class, 'index'])->name('saus-news.index');

    // News management
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
    Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit');

    // Contact persons
    Route::get('/contact-persons', [ContactPersonController::class, 'index'])->name('contact-persons.index');
    Route::post('/contact-persons', [ContactPersonController::class, 'store'])->name('contact-persons.store');
    Route::put('/contact-persons/{contactPerson}', [ContactPersonController::class, 'update'])->name('contact-persons.update');
    Route::post('/contact-persons/{contactPerson}/toggle', [ContactPersonController::class, 'toggle'])->name('contact-persons.toggle');

    // API endpoints (JSON, CSRF protected)
    Route::prefix('api')->group(function () {
        // Ticket API
        Route::put('/tickets/{ticket}', [TicketApiController::class, 'update'])->name('api.tickets.update');
        Route::post('/tickets/{ticket}/status', [TicketApiController::class, 'updateStatus'])->name('api.tickets.status');
        Route::post('/tickets/{ticket}/assignee', [TicketApiController::class, 'updateAssignee'])->name('api.tickets.assignee');
        Route::post('/tickets/{ticket}/follow-up', [TicketApiController::class, 'updateFollowUp'])->name('api.tickets.follow-up');
        Route::get('/tickets/{ticket}/votes', [TicketApiController::class, 'getVotes'])->name('api.tickets.votes');

        // Comment API
        Route::post('/tickets/{ticket}/comments', [CommentApiController::class, 'store'])->name('api.comments.store');
        Route::put('/comments/{comment}', [CommentApiController::class, 'update'])->name('api.comments.update');
        Route::post('/comments/{comment}/visibility', [CommentApiController::class, 'toggleVisibility'])->name('api.comments.visibility');

        // Vote API
        Route::post('/tickets/{ticket}/vote', [VoteApiController::class, 'voteTicket'])->name('api.tickets.vote');
        Route::post('/comments/{comment}/vote', [VoteApiController::class, 'voteComment'])->name('api.comments.vote');

        // Attachment API
        Route::post('/tickets/{ticket}/attachments', [AttachmentApiController::class, 'store'])->name('api.attachments.store');
        Route::get('/attachments/{attachment}', [AttachmentApiController::class, 'show'])->name('api.attachments.show');
        Route::delete('/attachments/{attachment}', [AttachmentApiController::class, 'destroy'])->name('api.attachments.destroy');

        // News API
        Route::post('/news', [NewsApiController::class, 'store'])->name('api.news.store');
        Route::put('/news/{news}', [NewsApiController::class, 'update'])->name('api.news.update');
        Route::delete('/news/{news}', [NewsApiController::class, 'destroy'])->name('api.news.destroy');
        Route::get('/news/{news}/image', [NewsApiController::class, 'image'])->name('api.news.image');

        // Contact Person API
        Route::post('/tickets/{ticket}/contact-persons', [ContactPersonApiController::class, 'linkToTicket'])->name('api.contact-persons.link');
        Route::delete('/tickets/{ticket}/contact-persons/{contactPerson}', [ContactPersonApiController::class, 'unlinkFromTicket'])->name('api.contact-persons.unlink');
    });
});

// Public routes (no auth required)
// Prefix is configurable to keep admin tool hidden from crawlers
// that discover the public pages (security by obscurity separation)
Route::prefix(config('saus.public_route_prefix', 'public-information'))->group(function () {
    Route::get('/', [PublicTicketController::class, 'index'])->name('public.tickets.index');
    Route::get('/news', [PublicNewsController::class, 'index'])->name('public.news.index');
    Route::get('/news/{news}', [PublicNewsController::class, 'show'])->name('public.news.show');
    Route::get('/api/news/{news}/image', [NewsApiController::class, 'image'])->name('public.news.image');
    Route::get('/imageview/{code}', [ImageViewController::class, 'show'])->name('public.imageview');
});
