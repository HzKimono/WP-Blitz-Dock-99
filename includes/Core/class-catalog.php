<?php
namespace BlitzDock\Core;

defined('ABSPATH') || exit;

class Catalog {
    /** Return the full map of supported social platforms. Keys must match settings. */
    public static function get_social_platforms(): array {
        return [
            '9gag' => __('9gag','blitz-dock'),
            'airbnb' => __('Airbnb','blitz-dock'),
            'avatar' => __('Avatar','blitz-dock'),
            'behance' => __('Behance','blitz-dock'),
            'default' => __('Default','blitz-dock'),
            'devianart' => __('Devianart','blitz-dock'),
            'discord' => __('Discord','blitz-dock'),
            'dribbble' => __('Dribbble','blitz-dock'),
            'dropbox' => __('Dropbox','blitz-dock'),
            'e-commerce' => __('E Commerce','blitz-dock'),
            'email' => __('Email','blitz-dock'),
            'etsy' => __('Etsy','blitz-dock'),
            'evernote' => __('Evernote','blitz-dock'),
            'facebook' => __('Facebook','blitz-dock'),
            'faq' => __('Faq','blitz-dock'),
            'flickr' => __('Flickr','blitz-dock'),
            'foursquare' => __('Foursquare','blitz-dock'),
            'git' => __('Git','blitz-dock'),
            'google-plus' => __('Google Plus','blitz-dock'),
            'instagram' => __('Instagram','blitz-dock'),
            'line' => __('Line','blitz-dock'),
            'linkedin' => __('Linkedin','blitz-dock'),
            'live-chat' => __('Live Chat','blitz-dock'),
            'map' => __('Map','blitz-dock'),
            'messages' => __('Messages','blitz-dock'),
            'messenger' => __('Messenger','blitz-dock'),
            'myspace' => __('Myspace','blitz-dock'),
            'outlook' => __('Outlook','blitz-dock'),
            'path' => __('Path','blitz-dock'),
            'periscope' => __('Periscope','blitz-dock'),
            'phone' => __('Phone','blitz-dock'),
            'pinterest' => __('Pinterest','blitz-dock'),
            'quora' => __('Quora','blitz-dock'),
            'reddit' => __('Reddit','blitz-dock'),
            'skype' => __('Skype','blitz-dock'),
            'snapchat' => __('Snapchat','blitz-dock'),
            'soundcloud' => __('Soundcloud','blitz-dock'),
            'spotify' => __('Spotify','blitz-dock'),
            'swarm' => __('Swarm','blitz-dock'),
            'telegram' => __('Telegram','blitz-dock'),
            'tiktok' => __('Tiktok','blitz-dock'),
            'tumblr' => __('Tumblr','blitz-dock'),
            'twitch' => __('Twitch','blitz-dock'),
            'twitter' => __('Twitter','blitz-dock'),
            'viber' => __('Viber','blitz-dock'),
            'vimeo' => __('Vimeo','blitz-dock'),
            'vine' => __('Vine','blitz-dock'),
            'wattpad' => __('Wattpad','blitz-dock'),
            'wechat' => __('Wechat','blitz-dock'),
            'whatsapp' => __('Whatsapp','blitz-dock'),
            'yelp' => __('Yelp','blitz-dock'),
            'youtube' => __('Youtube','blitz-dock'),
            // ...continue with your full set (WhatsApp, Telegram, etc.)
        ];
    }
}