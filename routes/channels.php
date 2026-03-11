<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('quiz.session.{code}', function () {
    return true; // quiz yayını herkese açık (public display, participant vs.)
});
