<?php

test('the home page introduces the mcp server', function () {
    $this->get('/')->assertOk()->assertSee('Harborboard');
});
