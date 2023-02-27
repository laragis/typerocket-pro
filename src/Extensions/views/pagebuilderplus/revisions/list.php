<?php
/**
 * @var \App\Models\BuilderRevision[] $revs
 */

if(empty($revs)) {
    echo "<p style='padding: 10px 12px'>No builder revisions found.</p>";
    return;
}
?>
<style>
    .revs-list {
        padding: 4px 12px 0;
    }

    .revs-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 5px;
    }

    .revs-list li + li {
        border-top: none;
    }

    .revs-list img {
        max-width: 20px;
        height: auto;
    }
</style>
<ul class="revs-list">
    <?php foreach ($revs as $rev) :
        try {
            $difference = \TypeRocket\Utility\DateTime::getDiffDateIntervalFromNow($rev->created_at);
        } catch (\Throwable $t) {
            $difference = null;
        }

        if(is_null($difference)) {
            continue;
        }

        $ago = \TypeRocket\Utility\DateTime::agoFormatFromDateDiff($difference);
        $avatar = null;
        $author_name = 'unknown';

        if($rev_user = $rev->user) {
            $avatar = $rev_user->getID();
            $author_name = $rev_user->display_name;
        }
    ?>
        <li>
            <?= get_avatar($avatar); ?>
            <div>
                <?=  $author_name; ?>, <?= $ago ?>
                (<a href="<?= $rev->getSearchUrl(); ?>"><?= $rev->created_at; ?></a>)
            </div>
        </li>
    <?php endforeach; ?>
</ul>