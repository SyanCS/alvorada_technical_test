<?php
$propertyData = $property;
$notes = $propertyData['notes'] ?? [];
$createdDate = new DateTime($propertyData['created_at']);
?>

<style>
.property-header {
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.property-name {
    font-size: 28px;
    color: #333;
    margin-bottom: 10px;
    font-weight: 600;
}

.property-address {
    color: #666;
    font-size: 15px;
    line-height: 1.5;
}

.property-meta {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.meta-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.meta-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
    font-weight: 600;
}

.meta-value {
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.section-title {
    font-size: 20px;
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.add-note-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    min-height: 100px;
    transition: border-color 0.3s;
}

.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 100%;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.notes-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.note-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.note-content {
    color: #333;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 8px;
}

.note-meta {
    color: #999;
    font-size: 11px;
}

.no-notes {
    text-align: center;
    padding: 30px;
    color: #999;
    font-size: 14px;
}

.success-message {
    background: #d4edda;
    border: 2px solid #c3e6cb;
    color: #155724;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}

.error-message {
    background: #f8d7da;
    border: 2px solid #f5c6cb;
    color: #721c24;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}
</style>

<div class="property-header">
    <div class="property-name"><?php echo htmlspecialchars($propertyData['name']); ?></div>
    <div class="property-address">📍 <?php echo htmlspecialchars($propertyData['address']); ?></div>
</div>

<div class="property-meta">
    <div class="meta-item">
        <div class="meta-label">Latitude</div>
        <div class="meta-value"><?php echo number_format($propertyData['latitude'], 6); ?>°</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Longitude</div>
        <div class="meta-value"><?php echo number_format($propertyData['longitude'], 6); ?>°</div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Created</div>
        <div class="meta-value"><?php echo $createdDate->format('M j, Y'); ?></div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Total Notes</div>
        <div class="meta-value"><?php echo count($notes); ?></div>
    </div>
</div>

<div class="section-title">📝 Notes</div>

<div id="messageContainer"></div>

<div class="add-note-form">
    <form id="addNoteForm">
        <div class="form-group">
            <label for="noteText">Add a Note</label>
            <textarea 
                id="noteText" 
                name="note" 
                placeholder="Enter your note here... (minimum 3 characters)"
                required
                minlength="3"
            ></textarea>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            ✍️ Add Note
        </button>
    </form>
</div>

<div id="notesListContainer">
    <div class="notes-list" id="notesList">
        <?php if (empty($notes)): ?>
            <div class="no-notes">No notes yet. Add your first note above!</div>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
                <?php $noteDate = new DateTime($note['created_at']); ?>
                <div class="note-item">
                    <div class="note-content"><?php echo htmlspecialchars($note['note']); ?></div>
                    <div class="note-meta">
                        Added on <?php echo $noteDate->format('M j, Y \a\t g:i A'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const propertyId = <?php echo $propertyData['id']; ?>;

// Add note via AJAX
async function addNote(noteText) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Adding...';

    try {
        const response = await fetch('/api/add_note.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                property_id: propertyId,
                note: noteText
            })
        });

        const data = await response.json();

        if (data.success) {
            showMessage('✅ Note added successfully!', 'success');
            document.getElementById('noteText').value = '';
            
            // Reload page to show new note
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showMessage('❌ ' + (data.message || 'Failed to add note'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('❌ Failed to add note. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '✍️ Add Note';
    }
}

// Show message
function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    const className = type === 'success' ? 'success-message' : 'error-message';
    
    container.innerHTML = `<div class="${className}">${message}</div>`;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

// Form submit handler
document.getElementById('addNoteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const noteText = document.getElementById('noteText').value.trim();
    
    if (noteText.length < 3) {
        showMessage('❌ Note must be at least 3 characters long', 'error');
        return;
    }

    await addNote(noteText);
});
</script>
