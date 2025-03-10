document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.vote-btn').forEach(button => {
        button.addEventListener('click', function () {
            let storyId = this.closest('.purerawz-story-card').getAttribute('data-story-id');
            let voteType = this.getAttribute('data-vote');

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=purerawz_vote&story_id=${storyId}&vote_type=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.purerawz-story-card').querySelector('.like-count').innerText = data.likes;
                    this.closest('.purerawz-story-card').querySelector('.dislike-count').innerText = data.dislikes;

                    this.classList.add('voted');
                    this.parentNode.querySelectorAll('.vote-btn').forEach(btn => {
                        btn.classList.remove('voted');
                    });
                    this.classList.add('voted');
                } else {
                    alert(data.message);
                }
            });
        });
    });
});