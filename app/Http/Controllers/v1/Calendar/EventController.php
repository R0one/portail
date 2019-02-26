<?php
/**
 * Gère les événements des calendriers.
 *
 * TODO: En scopes
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Http\Controllers\v1\Calendar;

use App\Http\Controllers\v1\Controller;
use App\Traits\Controller\v1\HasCalendars;
use App\Http\Requests\CalendarEventRequest;
use App\Models\User;
use App\Models\Asso;
use App\Models\Event;
use App\Models\Calendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Interfaces\CanHaveCalendars;
use App\Traits\HasVisibility;

class EventController extends Controller
{
    use HasCalendars;

    /**
     * Nécessité de gérer les événements des calendriers.
     */
    public function __construct()
    {
        $this->middleware(
	        array_merge(
		        \Scopes::allowPublic()->matchOneOfDeepestChildren('user-get-calendars', 'client-get-calendars'),
		        \Scopes::allowPublic()->matchOneOfDeepestChildren('user-get-events', 'client-get-events')
	        ),
	        ['only' => ['index', 'show']]
        );
        $this->middleware(
	        array_merge(
		        \Scopes::matchOneOfDeepestChildren('user-edit-calendars', 'client-edit-calendars'),
		        \Scopes::matchOneOfDeepestChildren('user-get-events', 'client-get-events')
	        ),
	        ['only' => ['update', 'store', 'destroy']]
        );
    }

    /**
     * Liste des événements du calendrier.
     *
     * @param Request	$request
     * @param string 	$calendar_id
     * @return JsonResponse
     */
    public function index(Request $request, string $calendar_id): JsonResponse
    {
        $calendar = $this->getCalendar($request, \Auth::user(), $calendar_id);
        $events = $calendar->events()->getSelection();

        if (\Scopes::isOauthToken($request)) {
            $events = $events->filter(function ($event) use ($request) {
                return ($this->tokenCanSee($request, $event, 'get')
                    && (!\Auth::id() || $this->isVisible($event, \Auth::id())))
                    || $this->isEventFollowed($request, $event, \Auth::id());
            });
        } else {
            $events = $events->filter(function ($event) {
                return $this->isVisible($event);
            });
        }

        return response()->json($events->values()->map(function ($event) {
            return $event->hideData();
        }), 200);
    }

    /**
     * Ajoute un événement au calendrier.
     *
     * @param CalendarEventRequest	$request
     * @param string               $calendar_id
     * @return JsonResponse
     */
    public function store(CalendarEventRequest $request, string $calendar_id): JsonResponse
    {
        $user = \Auth::user();
        $calendar = $this->getCalendar($request, $user, $calendar_id);

        $events = [];

        if ($request->filled('event_ids')) {
            foreach ($request->input('event_ids') as $event_id) {
                $events[] = $this->getEvent($request, $user, $event_id);
                $calendar->events()->attach(end($events));
            }
        } else {
            $events[] = $this->getEvent($request, $user, $request->input('event_id'));
            $calendar->events()->attach($events[0]);
        }

        foreach ($events as $event) {
            $event = $event->hideData();
        }

        return response()->json($events, 201);
    }

    /**
     * Montre un événement du calendrier.
     *
     * @param Request	$request
     * @param string 	$calendar_id
     * @param string 	$event_id
     * @return JsonResponse
     */
    public function show(Request $request, string $calendar_id, string $event_id): JsonResponse
    {
        $calendar = $this->getCalendar($request, \Auth::user(), $calendar_id);
        $event = $this->getEventFromCalendar($request, \Auth::user(), $calendar, $event_id);

        return response()->json($event->hideData(), 200);
    }

    /**
     * Il est impossible de modifier un événement du calendrier.
     *
     * @param CalendarEventRequest	$request
     * @param string               $calendar_id
     * @param string               $event_id
     * @return void
     */
    public function update(CalendarEventRequest $request, string $calendar_id, string $event_id): void
    {
        abort(405);
    }

    /**
     * Retire un événement du calendrier.
     *
     * @param Request	$request
     * @param string 	$calendar_id
     * @param string 	$event_id
     * @return void
     */
    public function destroy(Request $request, string $calendar_id, string $event_id): void
    {
        $calendar = $this->getCalendar($request, \Auth::user(), $calendar_id);
        $event = $this->getEventFromCalendar($request, \Auth::user(), $calendar, $event_id);

        $calendar_ids = $event->owner->calendars()->get(['calendars.id'])->pluck('id');
        $event_calendar_ids = $event->calendars()->get(['calendars.id'])->pluck('id');

        // On vérifie que celui qui possède l'event, possède l'événement dans au moins 2 de ses calendriers.
        if (count($calendar_ids->intersect($event_calendar_ids)) === 1 && $calendar_ids->contains($calendar_id)) {
            abort(400, 'L\'événement doit au moins appartenir à un calendrier du propriétaire de l\'événement');
        }

        $calendar->events()->detach($event);

        abort(204);
    }
}
