{{-- Taskify v2 — Docked task inspector (design-system right column).
     Always visible beside the board/list. Populated by JS from existing
     task endpoints (tasks.list / tasks.info / tasks.get-media) on task
     click — no controller/route changes. Tabs: Subtask · Comments ·
     Timeline · Media (embedded) + Activity (deep-link). --}}
@php $inspUser = getAuthenticatedUser(); @endphp
<aside class="tk-inspector-dock" id="task_inspector"
       data-task-info-url="{{ url('tasks/information') }}"
       data-tasks-list-url="{{ url('tasks/list') }}"
       data-task-media-url="{{ url('tasks/get-media') }}"
       data-task-get-url="{{ url('tasks/get') }}"
       data-storage-url="{{ asset('storage') }}"
       data-no-image="{{ asset('storage/photos/no-image.jpg') }}"
       data-comment-url="{{ url('tasks/information') }}">
    <div class="tk-inspector-head">
        <div class="tk-inspector-head-l">
            <span class="mono tk-insp-code" id="tk_insp_code">—</span>
            <span class="tk-insp-status" id="tk_insp_status"></span>
        </div>
        <div class="tk-inspector-head-r">
            <a href="javascript:void(0);" class="tk-ic-btn" id="tk_insp_open" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="{{ get_label('open_full_task', 'Open full task') }}"><x-tk-icon name="eye" /></a>
        </div>
    </div>

    <div class="tk-inspector-scroll" id="tk_insp_scroll">
        <div class="tk-insp-empty" id="tk_insp_empty">
            <x-tk-icon name="task" size="22" />
            <p>{{ get_label('select_a_task', 'Select a task to see its details') }}</p>
        </div>

        <div class="tk-insp-content" id="tk_insp_content" style="display:none;">
            <h2 class="tk-insp-title" id="tk_insp_title"></h2>
            <div class="tk-insp-grid" id="tk_insp_meta"></div>

            <div class="tk-insp-tabs" id="tk_insp_tabs">
                <button type="button" class="tk-insp-tab active" data-insp-tab="subtask">{{ get_label('subtasks', 'Subtasks') }}</button>
                <button type="button" class="tk-insp-tab" data-insp-tab="comments">{{ get_label('comments', 'Comments') }}</button>
                <button type="button" class="tk-insp-tab" data-insp-tab="timeline">{{ get_label('timeline', 'Timeline') }}</button>
                <button type="button" class="tk-insp-tab" data-insp-tab="media">{{ get_label('media', 'Media') }}</button>
                <button type="button" class="tk-insp-tab" data-insp-tab="activity">{{ get_label('activity', 'Activity') }}</button>
            </div>
            <div class="tk-insp-panes">
                <div class="tk-insp-pane active" data-insp-pane="subtask" id="tk_insp_pane_subtask"></div>
                <div class="tk-insp-pane" data-insp-pane="comments" id="tk_insp_pane_comments"></div>
                <div class="tk-insp-pane" data-insp-pane="timeline" id="tk_insp_pane_timeline"></div>
                <div class="tk-insp-pane" data-insp-pane="media" id="tk_insp_pane_media"></div>
                <div class="tk-insp-pane" data-insp-pane="activity" id="tk_insp_pane_activity"></div>
            </div>
        </div>
    </div>

    @if ($inspUser && $inspUser->can('manage_tasks'))
    <div class="tk-inspector-foot" id="tk_insp_foot" style="display:none;">
        <form id="tk_insp_comment_form" class="tk-insp-commentbar" autocomplete="off">
            @csrf
            <input type="hidden" id="tk_insp_task_id" value="">
            <x-tk-icon name="msg" size="15" class="tk-insp-commentbar-ic" />
            <input type="text" id="tk_insp_comment_input" placeholder="{{ get_label('comment_placeholder', 'Comment, or type / for actions') }}">
            <button type="submit" class="btn btn-sm btn-primary" id="tk_insp_comment_submit">{{ get_label('post', 'Post') }}</button>
        </form>
    </div>
    @endif
</aside>
