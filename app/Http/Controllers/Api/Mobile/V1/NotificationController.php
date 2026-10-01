<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Resources\Mobile\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends MobileController
{
    public function index(Request $request)
    {
        $query = $request->user()->notifications()->latest();
        $category = $request->get('category', 'all');
        $unread = $request->boolean('unread');

        if ($unread) {
            $query->whereNull('read_at');
        }

        $items = $query->paginate(min((int) $request->get('per_page', 30), 50));
        $collection = NotificationResource::collection($items);

        if (in_array($category, ['leads', 'tasks', 'follow-ups'], true)) {
            $filtered = $items->getCollection()->filter(function (DatabaseNotification $notification) use ($category) {
                $resource = (new NotificationResource($notification))->toArray(request());

                return ($resource['category'] ?? '') === $category;
            })->values();
            $items->setCollection($filtered);
        }

        return NotificationResource::collection($items)->additional([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $item = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        return $this->ok(new NotificationResource($item->fresh()), 'Marked as read.');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->ok(null, 'All notifications marked as read.');
    }
}
