<?php
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$container = new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($container);
$translator = new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader, 'en');
$container->instance('validator', new Illuminate\Validation\Factory($translator, $container));
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$service = new App\Services\BrandSiteTheme;
$valid = ['version'=>1,'title'=>'Fasta','description'=>'Fresh','accent'=>'#039D55','motion'=>'fade','sections'=>['hero','catalog']];
if ($service->validate($valid) !== $valid) throw new RuntimeException('Valid theme changed');
$invalid = [
    array_merge($valid,['version'=>2]),
    array_merge($valid,['script'=>'alert(1)']),
    array_merge($valid,['sections'=>['hero','hero']]),
    array_merge($valid,['sections'=>['checkout']]),
    array_merge($valid,['sections'=>['x'=>'hero']]),
    array_merge($valid,['accent'=>'red; background:url(x)']),
    array_merge($valid,['motion'=>'execute']),
    array_merge($valid,['title'=>str_repeat('a',121)]),
    array_merge($valid,['publish'=>true]),
    null,
];
foreach ($invalid as $index=>$theme) {
    try { $service->validate($theme); }
    catch (Illuminate\Validation\ValidationException $exception) { continue; }
    throw new RuntimeException('Accepted invalid theme '.$index);
}
echo "Valid theme and 10 invalid cases passed.\n";
