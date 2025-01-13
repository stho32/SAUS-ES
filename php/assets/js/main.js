// Main JavaScript für SAUS-ES

document.addEventListener('DOMContentLoaded', function() {
    // Vote Buttons
    const voteButtons = document.querySelectorAll('.vote-button');
    voteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const ticketId = this.dataset.ticketId;
            const voteType = this.dataset.voteType;
            const username = this.dataset.username;
            
            fetch('api/vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    vote_type: voteType,
                    username: username
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateVoteCount(ticketId);
                    this.classList.toggle('active');
                } else {
                    alert(data.message || 'Ein Fehler ist aufgetreten');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
        });
    });

    // Partner Link Kopieren
    const copyButtons = document.querySelectorAll('.copy-link');
    copyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const link = this.dataset.link;
            
            navigator.clipboard.writeText(link).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Kopiert!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Fehler beim Kopieren:', err);
                alert('Fehler beim Kopieren des Links');
            });
        });
    });
});

// Hilfsfunktion zum Aktualisieren der Vote-Anzahl
function updateVoteCount(ticketId) {
    fetch(`api/get_votes.php?ticket_id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`#up-votes-${ticketId}`).textContent = data.up_votes;
                document.querySelector(`#down-votes-${ticketId}`).textContent = data.down_votes;
            }
        })
        .catch(error => console.error('Error:', error));
}
