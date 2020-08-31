<?php namespace Dmrch\BlogExtension;

use Backend;
use Event;
use DB;
use System\Classes\PluginBase;

/**
 * BlogExtension Plugin Information File
 */
class Plugin extends PluginBase
{

    public $content_html;

    public $require = ['RainLab.Blog'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Blog Extension',
            'description' => 'Extension for RainLab.Blog',
            'author'      => 'Angelo Demarchi',
            'icon'        => 'icon-pencil'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
       \RainLab\Blog\Models\Post::extend(function($model) {
            $model->rules = [
                'title'   => 'required',
                'slug'    => ['required', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:rainlab_blog_posts'],
                'content_html' => 'required',
                'excerpt' => ''
            ];

            $model->translatable = [
                'title',
                'content',
                'content_html',
                'excerpt',
                'metadata',
                ['slug', 'index' => true]
            ];

            $model->bindEvent('model.beforeSave', function() use ($model) {
                $this->content_html = $model->content_html;
                $model->content = strip_tags($model->content_html);
            });

            $model->bindEvent('model.afterSave', function() use ($model) {
                Db::table('rainlab_blog_posts')->where('id', $model->id)->update(['content_html' => $this->content_html]);
            });
        });

        // Extend all backend form usage
        Event::listen('backend.form.extendFields', function($widget) {

            // Only for the User controller
            if (!$widget->getController() instanceof \RainLab\Blog\Controllers\Posts) {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof \RainLab\Blog\Models\Post) {
                return;
            }   

            $widget->addSecondaryTabFields([
                'content_html' => [
                    'tab' => 'rainlab.blog::lang.post.tab_edit',
                    'type' => 'richeditor',
                    'toolbarButtons' => 'fullscreen|bold|italic|underline|strikeThrough|subscript|superscript|fontFamily|fontSize|color|emoticons|inlineStyle|paragraphStyle|paragraphFormat|align|formatOL|formatUL|outdent|indent|quote|insertHR|insertLink|insertImage|insertVideo|insertAudio|insertFile|insertTable|undo|redo|clearFormatting|selectAll|html',
                    'cssClass' => 'field-slim blog-post-preview',
                    'stretch' => 'true',
                    'mode' => 'split',
                    'order' => 0
                ]
            ]);

            // Remove a Content field
            $widget->removeField('content');

        });
    }
}