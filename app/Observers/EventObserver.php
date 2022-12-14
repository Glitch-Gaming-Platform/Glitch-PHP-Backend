<?php

namespace App\Observers;

use App\Facades\EventsFacade;
use App\Invirtu\InvirtuClient;
use App\Models\Event;
use Exception;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function created(Event $event)
    {
        
        /**
         * After an event is created, we want to get the live stream information
         * for that event from Invirtu.
         */
        $orgnaizder_id = env('INVIRTU_ORGANIZER_ID', '');

        $organizer_token = env('INVIRTU_ORGANIZER_TOKEN', '');

        $default_template_id = env('INVIRTU_DEFAULT_TEMPALTE_ID', '');

        if($organizer_token && $organizer_token) {

            $client = new InvirtuClient($organizer_token);

            $data = ['organizer_id' => $orgnaizder_id, 'type' => 7, 'event_title' => $event->title, 'event_description' => $event->description];

            if($default_template_id) {
                $data['template_id'] = $default_template_id;
            }

            $result = $client->events->create($data);

            if($result->status == 'success') {
                $event->forceFill([
                    'invirtu_id' => $result->data->id,
                    'invirtu_webrtc_url' => $result->data->embed_video_chat,
                    'invirtu_broadcast_url'=> $result->data->webview_broadcast,
                    'invirtu_rtmp_broadcast_endpoint'=> $result->data->rtmp_broadcast_endpoint,
                    'invirtu_rtmp_broadcast_key' => $result->data->rtmp_broadcast_key,
                    'invirtu_rtmp_livestream_endpoint'=> $result->data->rtmp_ingestion_endpoint,
                    'invirtu_rtmp_livestream_key' => $result->data->rtmp_ingestion_key,
                ]);

                $event->save();

                $result = $client->events->setPreference($result->data->id, ['key' => 'glitch_stream_id', 'value' => $event->id]);

                try {
                    
                    $webhook_data = [
                        'url' => env('WEBHOOK_INVIRTU_URL'),
                        'method' => 'POST',
                        'auth_method' => 1,
                        'auth_key' => env('WEBHOOK_INVIRTU_SIGNATURE_KEY'),
                        'auth_value' => env('WEBHOOK_INVIRTU_SIGNATURE_SECRET')
                    ];

                    $client->events->addWebhook($result->data->id, $webhook_data);

                } catch(Exception $e) {

                }

            } else {
                Log::error('Unable to create Invirtu Event', (array)$result->errors);
            }
        } else {
            Log::error('Both an invirtu organizer ID and token is required to create an event.');
        }

        EventsFacade::createDefaultOverlays($event);

    }

    /**
     * Handle the Event "updated" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function updated(Event $event)
    {
        //
    }

    /**
     * Handle the Event "deleted" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function deleted(Event $event)
    {
        //
    }

    /**
     * Handle the Event "restored" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function restored(Event $event)
    {
        //
    }

    /**
     * Handle the Event "force deleted" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function forceDeleted(Event $event)
    {
        //
    }
}
