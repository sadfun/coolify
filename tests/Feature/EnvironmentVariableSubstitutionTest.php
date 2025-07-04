<?php

test('substituteEnvironmentVariables handles fallback syntax correctly', function () {
    // Test fallback syntax with missing variable
    $result = substituteEnvironmentVariables('${EXTERNAL_RESOURCE_DIR:-./workspace}:/app/ext', []);
    expect($result)->toBe('./workspace:/app/ext');

    // Test fallback syntax with existing variable
    $result = substituteEnvironmentVariables('${EXTERNAL_RESOURCE_DIR:-./workspace}:/app/ext', ['EXTERNAL_RESOURCE_DIR' => '/custom/path']);
    expect($result)->toBe('/custom/path:/app/ext');

    // Test simple variable reference
    $result = substituteEnvironmentVariables('${DATA_DIR}:/app/data', ['DATA_DIR' => '/var/data']);
    expect($result)->toBe('/var/data:/app/data');

    // Test multiple variables with mixed fallbacks
    $result = substituteEnvironmentVariables('${BASE_DIR:-/default}/${SUB_DIR:-subdir}:/app/target', ['BASE_DIR' => '/custom']);
    expect($result)->toBe('/custom/subdir:/app/target');

    // Test no variables to substitute
    $result = substituteEnvironmentVariables('./normal/path:/app/normal', []);
    expect($result)->toBe('./normal/path:/app/normal');
});

test('substituteEnvironmentVariables resolves original issue', function () {
    // This is the exact scenario from the bug report
    $volumeString = '${EXTERNAL_RESOURCE_DIR:-./workspace}:/app/ext';
    $allEnvironments = []; // No environment variables set

    // Apply substitution
    $substituted = substituteEnvironmentVariables($volumeString, $allEnvironments);

    // Extract source and target like the original code does
    $source = str($substituted)->before(':');
    $target = str($substituted)->after(':')->beforeLast(':');

    // The source should be the fallback value, not contain the problematic pattern
    expect($source->value())->toBe('./workspace');
    expect($target->value())->toBe('/app/ext');

    // Most importantly, the source should not contain the problematic ':-' pattern
    expect($source->contains(':-'))->toBeFalse();
});

test('substituteEnvironmentVariables works with Laravel collections', function () {
    $envCollection = collect(['EXTERNAL_RESOURCE_DIR' => '/custom/path']);

    $result = substituteEnvironmentVariables('${EXTERNAL_RESOURCE_DIR:-./workspace}:/app/ext', $envCollection);
    expect($result)->toBe('/custom/path:/app/ext');
});

test('substituteEnvironmentVariables handles single dash fallback syntax correctly', function () {
    // Test single dash fallback syntax with missing variable
    $result = substituteEnvironmentVariables('${MISSING_VAR-/default/path}:/app/ext', []);
    expect($result)->toBe('/default/path:/app/ext');

    // Test single dash fallback syntax with existing variable
    $result = substituteEnvironmentVariables('${MISSING_VAR-/default/path}:/app/ext', ['MISSING_VAR' => '/custom/path']);
    expect($result)->toBe('/custom/path:/app/ext');

    // Test single dash fallback syntax with empty variable (should return empty, not fallback)
    $result = substituteEnvironmentVariables('${MISSING_VAR-/default/path}:/app/ext', ['MISSING_VAR' => '']);
    expect($result)->toBe(':/app/ext');

    // Test multiple variables with single dash fallbacks
    $result = substituteEnvironmentVariables('${BASE_DIR-/default}/${SUB_DIR-subdir}:/app/target', ['BASE_DIR' => '/custom']);
    expect($result)->toBe('/custom/subdir:/app/target');
});

test('substituteEnvironmentVariables demonstrates difference between :- and - syntax', function () {
    // Test with empty string value
    $envWithEmpty = ['TEST_VAR' => ''];

    // With :- syntax, empty string should use fallback
    $result1 = substituteEnvironmentVariables('${TEST_VAR:-/fallback}', $envWithEmpty);
    expect($result1)->toBe('/fallback');

    // With - syntax, empty string should NOT use fallback
    $result2 = substituteEnvironmentVariables('${TEST_VAR-/fallback}', $envWithEmpty);
    expect($result2)->toBe('');

    // Test with null value
    $envWithNull = ['TEST_VAR' => null];

    // With :- syntax, null should use fallback
    $result3 = substituteEnvironmentVariables('${TEST_VAR:-/fallback}', $envWithNull);
    expect($result3)->toBe('/fallback');

    // With - syntax, null should NOT use fallback (variable is set, just null)
    $result4 = substituteEnvironmentVariables('${TEST_VAR-/fallback}', $envWithNull);
    expect($result4)->toBe('');

    // Test with unset variable - both should use fallback
    $result5 = substituteEnvironmentVariables('${UNSET_VAR:-/fallback}', []);
    expect($result5)->toBe('/fallback');

    $result6 = substituteEnvironmentVariables('${UNSET_VAR-/fallback}', []);
    expect($result6)->toBe('/fallback');
});

test('substituteEnvironmentVariables handles the requested single dash syntax', function () {
    $volumeString = '${MISSING_VAR-/default/path}:/app/ext';
    $allEnvironments = [];

    $substituted = substituteEnvironmentVariables($volumeString, $allEnvironments);

    $source = str($substituted)->before(':');
    $target = str($substituted)->after(':')->beforeLast(':');

    // The source should be the fallback value
    expect($source->value())->toBe('/default/path');
    expect($target->value())->toBe('/app/ext');

    // The source should not contain the problematic '-' pattern
    expect($source->contains('-'))->toBeFalse();
    expect($substituted)->toBe('/default/path:/app/ext');
});
