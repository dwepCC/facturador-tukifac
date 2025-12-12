@extends('tenant.layouts.app')

@push('styles')
    <style type="text/css">
        /*.v-modal {
            opacity: 0.2 !important;
        }
        .border-custom {
            border-color: rgba(0,136,204, .5) !important;
        }
        @media only screen and (min-width: 768px) {
        	.inner-wrapper {
			    padding-top: 60px !important;
			}
        }*/
    </style>
@endpush
 
@section('content')
{{--tukifac--}}
         <div class="page-header pr-0">
            <h2>
                <a href="/documents">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        style="margin-top: -5px;"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="feather feather-file-text"
                    >
                        <path
                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                        ></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Nuevo Documento</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a href="/sale-notes" class="btn btn-custom btn-sm mt-2 mr-2">
                    <i class="fa fa-arrow-left"></i> 
                    Volver
                </a>
            </div>
        </div>
        {{--end tukifac--}}
    <tenant-documents-invoice-generate
        :is_contingency="{{ json_encode($is_contingency) }}"
        :type-user="{{json_encode(Auth::user()->type)}}"
        :auth-user="{{json_encode(Auth::user()->getDataOnlyAuthUser())}}"
        :configuration="{{\App\Models\Tenant\Configuration::getPublicConfig()}}"
        :document-id="{{ $documentId ?? 0 }}"
        :is-update="{{ json_encode($isUpdate ?? false) }}"
        :table="{{ json_encode($table ?? null) }}"
        :table-id="{{ json_encode($table_id ?? null) }}"
        :id-user="{{json_encode(Auth::user()->id)}}"></tenant-documents-invoice-generate>
@endsection

@push('scripts')
<script type="text/javascript">
	var count = 0;
	$(document).on("click", "#card-click", function(event){
		count = count + 1;
		if (count == 1) {
			$("#card-section").removeClass("card-collapsed");
		}
	});
</script>

    <!-- QZ -->
    <script src="{{ asset('js/sha-256.min.js') }}"></script>
    <script src="{{ asset('js/qz-tray.js') }}"></script>
    <script src="{{ asset('js/rsvp-3.1.0.min.js') }}"></script>
    <script src="{{ asset('js/jsrsasign-all-min.js') }}"></script>
    <script src="{{ asset('js/sign-message.js') }}"></script>
    <script src="{{ asset('js/function-qztray.js') }}"></script>
    <!-- END QZ -->

@endpush
