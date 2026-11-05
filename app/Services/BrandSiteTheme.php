<?php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** A versioned, data-only document shared by seller and support editing. */
class BrandSiteTheme
{
    public const TYPES = ['hero','catalog','banner','heading','text','image','image_text','cards','gallery','features','testimonials','faq','cta','contact','spacer','divider'];

    public function validate(mixed $theme): array
    {
        if (is_array($theme) && ($theme['version'] ?? null) === 2) return $this->versionTwo($theme);
        $input = Validator::make(['theme'=>$theme], [
            'theme'=>['required','array:version,title,description,accent,motion,sections'],
            'theme.version'=>['required','integer',Rule::in([1])],
            'theme.title'=>['required','string','max:120'],
            'theme.description'=>['present','nullable','string','max:600'],
            'theme.accent'=>['required','regex:/^#[0-9a-fA-F]{6}$/D'],
            'theme.motion'=>['required',Rule::in(['none','fade','rise'])],
            'theme.sections'=>['required','array','list','min:1','max:2'],
            'theme.sections.*'=>['required','distinct',Rule::in(['hero','catalog'])],
        ])->validate()['theme'];
        return ['version'=>1,'title'=>$input['title'],'description'=>$input['description'] ?? '',
            'accent'=>$input['accent'],'motion'=>$input['motion'],'sections'=>$input['sections']];
    }

    private function versionTwo(array $theme): array
    {
        $color=['nullable','regex:/^#[0-9a-fA-F]{6}$/D'];
        $url=['nullable','string','max:2048','regex:~^(?:https?://[^\s<>"\x27]+|/(?!/)[^\s<>"\x27]*)$~iD'];
        $rules=[
            'theme'=>['required','array:version,title,description,accent,motion,sections,settings,seo'],
            'theme.version'=>['required','integer',Rule::in([2])],
            'theme.title'=>['required','string','max:120'],
            'theme.description'=>['nullable','string','max:600'],
            'theme.accent'=>['required','regex:/^#[0-9a-fA-F]{6}$/D'],
            'theme.motion'=>['required',Rule::in(['none','fade','rise'])],
            'theme.settings'=>['sometimes','array:font,width,radius,background,footer,navigation'],
            'theme.settings.font'=>['sometimes',Rule::in(['system','editorial','rounded'])],
            'theme.settings.width'=>['sometimes',Rule::in(['standard','wide'])],
            'theme.settings.radius'=>['sometimes',Rule::in(['none','soft','round'])],
            'theme.settings.background'=>$color,
            'theme.settings.footer'=>['nullable','string','max:220'],
            'theme.settings.navigation'=>['sometimes','boolean'],
            'theme.seo'=>['sometimes','array:title,description,indexable'],
            'theme.seo.title'=>['nullable','string','max:160'],
            'theme.seo.description'=>['nullable','string','max:300'],
            'theme.seo.indexable'=>['sometimes','boolean'],
            'theme.sections'=>['required','array','list','min:1','max:40'],
            'theme.sections.*'=>['required','array:id,type,name,props'],
            'theme.sections.*.id'=>['required','string','distinct','regex:/^[a-z][a-z0-9-]{0,47}$/D'],
            'theme.sections.*.type'=>['required',Rule::in(self::TYPES)],
            'theme.sections.*.name'=>['sometimes','string','max:60'],
        ];
        foreach (array_slice(is_array($theme['sections'] ?? null) ? $theme['sections'] : [],0,40) as $i=>$block) {
            $base="theme.sections.$i.props";
            $rules[$base]=['required','array:eyebrow,heading,text,image,alt,button,href,align,background,color,spacing,columns,layout,items,height,hiddenMobile,email,phone,address'];
            foreach (['eyebrow'=>80,'heading'=>160,'text'=>3000,'alt'=>200,'button'=>60,'email'=>160,'phone'=>60,'address'=>500] as $key=>$limit) {
                $rules["$base.$key"]=['nullable','string',"max:$limit"];
            }
            $rules["$base.email"]=['nullable','email','max:160'];
            $rules["$base.image"]=$url;$rules["$base.href"]=$url;
            $rules["$base.background"]=$color;$rules["$base.color"]=$color;
            $rules["$base.align"]=['sometimes',Rule::in(['left','center','right'])];
            $rules["$base.spacing"]=['sometimes',Rule::in(['compact','normal','spacious'])];
            $rules["$base.layout"]=['sometimes',Rule::in(['split','cover','stack','left','right'])];
            $rules["$base.columns"]=['sometimes','integer','between:2,4'];
            $rules["$base.height"]=['sometimes','integer','between:16,160'];
            $rules["$base.hiddenMobile"]=['sometimes','boolean'];
            $rules["$base.items"]=['sometimes','array','list','max:16'];
            $rules["$base.items.*"]=['required','array:title,text,image,alt,href,label,question,answer,name,role,quote,rating'];
            foreach (['title'=>120,'text'=>600,'alt'=>200,'label'=>60,'question'=>180,'answer'=>1500,'name'=>80,'role'=>100,'quote'=>600] as $key=>$limit) {
                $rules["$base.items.*.$key"]=['nullable','string',"max:$limit"];
            }
            $rules["$base.items.*.image"]=$url;$rules["$base.items.*.href"]=$url;
            $rules["$base.items.*.rating"]=['sometimes','integer','between:1,5'];
        }
        return Validator::make(['theme'=>$theme],$rules)->validate()['theme'];
    }
}
