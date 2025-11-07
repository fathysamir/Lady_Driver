@extends('Website.layout.app')

@section('title', $title)

@section('content')

</section>
<div class="content" style="line-height: 1.8; font-size: 1rem; direction: {{ $terms->lang == 'ar' ? 'rtl' : 'ltr' }}; text-align: {{ $terms->lang == 'ar' ? 'right' : 'left' }};">
    {!! $content !!}
</div>

@endsection
