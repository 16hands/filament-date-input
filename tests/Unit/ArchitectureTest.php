<?php

arch('the date input package stands alone, with no application code')
    ->expect('SixteenHands\FilamentDateInput')
    ->not->toUse('App');
