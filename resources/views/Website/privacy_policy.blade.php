@extends('Website.layout.app')

@section('title', $title)

@section('content')

</section>
<div class="content" style="line-height: 1.8; font-size: 1rem; direction: {{ $privacy->lang == 'ar' ? 'rtl' : 'ltr' }}; text-align: {{ $privacy->lang == 'ar' ? 'right' : 'left' }};">
    {!! $content !!}
</div>

@endsection
