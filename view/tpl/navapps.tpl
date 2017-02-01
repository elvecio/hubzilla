<ul class="dropdown-menu" style="max-height: 80vh">
	{{foreach $apps as $app}}
	<li><a href="{{$app.url}}">{{if $icon}}<i class="app-icon fa fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="16" height="16" />{{/if}}&nbsp;{{$app.name}}</a></li>
	{{/foreach}}
	{{if $localuser}}
	<li class="divider"></li>
	<li><a href="/apps/edit"><i class="app-icon fa fa-plus-circle"></i>&nbsp;Add Apps</a></li>
	{{/if}}
</ul>
