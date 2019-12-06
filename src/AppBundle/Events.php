<?php

namespace AppBundle;

/**
 * This class defines the names of all the events dispatched
 */
final class Events
{
    /**
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     * @var string
     */
    const COMMENT_CREATED = 'comment.created';
}
