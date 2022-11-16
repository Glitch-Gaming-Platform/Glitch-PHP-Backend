<?php
namespace App\Facades;

use App\Models\Event;
use App\Models\EventInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EventInvitesFacade {


    public static function sendInvite(EventInvite $invite) {

        $event = Event::where('id', $invite->event_id)->first();

        Mail::send('email.eventInvite', ['invite' => $invite, 'event' => $event], function($message) use($invite, $event){

            $message->to($invite->email);
            $message->subject('Invited As Co-Host To ' . $event->title);
            $message->from(env('MAIL_FROM_ADDRESS', 'noreply@glitch.fun'),env('MAIL_FROM_NAME', 'Glitch Gaming'));
        });
    }

    public static function acceptInvite(EventInvite $invite, User $user) {

        $event = Event::where('id', $invite->event_id)->first();

        if(!$event->user_id) {

            $invite->forceFill([
                'user_id' => $user->id,
                'accepted_invite' => 1
            ]);

            $invite->save();

            RolesFacade::eventMakeSpeaker($event, $user);

        }

        return $invite;

    }
}