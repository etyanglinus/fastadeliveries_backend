<?php
$root=dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$container=new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($container);
$translator=new Illuminate\Translation\Translator(new Illuminate\Translation\ArrayLoader,'en');
$container->instance('validator',new Illuminate\Validation\Factory($translator,$container));
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$service=new App\Services\BrandSiteTheme;
$theme=json_decode(file_get_contents($root.'/tests/Fixtures/brandsite-v2.json'),true,512,JSON_THROW_ON_ERROR);
$valid=$service->validate($theme);
if(count($valid['sections'])!==16)throw new RuntimeException('Expected all 16 block types.');
$cases=[];
$bad=$theme;$bad['sections'][0]['props']['script']='alert(1)';$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['href']='javascript:alert(1)';$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['image']='file:///etc/passwd';$cases[]=$bad;
$bad=$theme;$bad['settings']['css']='body{display:none}';$cases[]=$bad;
$bad=$theme;$bad['sections'][1]['id']=$bad['sections'][0]['id'];$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['type']='script';$cases[]=$bad;
$bad=$theme;$bad['sections']=array_fill(0,41,$theme['sections'][0]);$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['items']=array_fill(0,17,['title'=>'Too many']);$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['email']='not-an-email';$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['color']='red;display:none';$cases[]=$bad;
$bad=$theme;$bad['seo']['indexable']='yes';$cases[]=$bad;
$bad=$theme;$bad['sections'][0]['props']['text']=str_repeat('a',3001);$cases[]=$bad;
$bad=$theme;$bad['version']=3;$cases[]=$bad;
foreach($cases as $i=>$bad){try{$service->validate($bad);}catch(Illuminate\Validation\ValidationException $e){continue;}throw new RuntimeException('Invalid case accepted: '.$i);}
echo "All 16 block types and 13 invalid-package cases passed.\n";
