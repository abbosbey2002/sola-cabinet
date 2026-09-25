{{-- ACTIVITY_ENABLED is off: say so plainly instead of showing zeros that
     read as "nobody used the cabinet". --}}
<section class="u-card">
    <x-empty icon="shield" :title="__('admin.journal_off.title')" :hint="__('admin.journal_off.hint')"/>
</section>
