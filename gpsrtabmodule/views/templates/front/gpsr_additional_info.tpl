{foreach from=$gpsr_entries item=entry}
    <div class="tab-pane fade" id="gpsr_{$entry.id_gpsr_entry}" role="tabpanel" style="opacity:1 ; display: block;">
        {$entry.gpsr_text nofilter}
    </div>
{/foreach}
