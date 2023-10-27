/*
 * This script will handle all javascript functions needed for the admin
 * pages.
 * Depends: jquery, photo.js
 */

Photo.Admin = {};
Photo.Admin.activeAlbum = null;
Photo.Admin.selectedCount = 0;

Photo.Admin.regenerateCover = function() {
    document.getElementById('coverPreview').style.display = "none";
    document.getElementById('coverSpinner').style.display = "block";

    fetch(URLHelper.url('admin_photo/album_cover', {'album_id': Photo.Admin.activeAlbum}), {
        method: 'POST',
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('coverPreview').src = '/data/' + data.coverPath;
            document.getElementById('coverSpinner').style.display = "none";
            document.getElementById('coverPreview').style.display = "block";
        } else {
            document.getElementById('coverSpinner').style.display = "none";
            document.getElementById('coverError').style.display = "block";
        }
    });
}

Photo.Admin.deleteAlbum = function() {
    document.getElementById('deleteConfirm').style.display = "none";
    document.getElementById('deleteProgress').style.display = "block";

    fetch(URLHelper.url('admin_photo/album_delete', {'album_id': Photo.Admin.activeAlbum}), {
        method: 'POST',
    })
    .then(() => {
        window.location = URLHelper.url('admin_photo');
        document.getElementById('deleteProgress').style.display = "none";
        document.getElementById('deleteDone').style.display = "block";
    });
}

Photo.Admin.deleteMultiple = function() {
    document.getElementById('multipleDeleteButton').style.display = "none";
    document.getElementById('multipleDeleteProgress').style.display = "block";

    let selectedThumbnails = document.querySelectorAll('.selectable-photo.selected');
    let fetchPromises = Array.from(selectedThumbnails).map(element => {
        return fetch(URLHelper.url('admin_photo/photo_delete', {'photo_id': element.dataset.photoId}), {
            method: 'POST',
        });
    });

    Promise.all(fetchPromises)
        .then(() => {
            location.reload();
        })
        .catch(() => {
            // TODO: add proper error handling
        });
}

Photo.Admin.moveMultiple = function() {
    document.getElementById('multipleMoveButton').style.display = "none";

    let selectedThumbnails = document.querySelectorAll('.selectable-photo.selected');
    let fetchPromises = Array.from(selectedThumbnails).map(element => {
        let formData = new FormData();
        formData.append('album_id', document.getElementById('newPhotoAlbum').value);

        return fetch(URLHelper.url('admin_photo/photo_move', {'photo_id': element.dataset.photoId}), {
            method: 'POST',
            body: formData,
        });
    });

    Promise.all(fetchPromises)
        .then(() => {
            location.reload();
        })
        .catch(() => {
            // TODO: add proper error handling
        });
}

Photo.Admin.moveAlbum = function() {
    document.getElementById('albumMoveSelect').style.display = "none";
    document.getElementById('albumMoveProgress').style.display = "block";

    let formData = new FormData();
    formData.append('parent_id', document.getElementById('newAlbumParent').value);

    fetch(URLHelper.url('admin_photo/album_move', {'album_id': Photo.Admin.activeAlbum}), {
        method: 'POST',
        body: formData,
    }).then(function() {
        // TODO: add proper error handling
        location.reload();
    });
}

Photo.Admin.init = function (albumId) {
    Photo.Admin.activeAlbum = albumId;
    const COUNT_SPAN = "<span class='selectedCount'>0</span>";
    document.getElementById('btnMultipleMove').innerHTML = document.getElementById('btnMultipleMove').innerHTML.replace('%i', COUNT_SPAN);
    document.getElementById('btnMultipleDelete').innerHTML = document.getElementById('btnMultipleDelete').innerHTML.replace('%i', COUNT_SPAN);

    // Add event listeners
    document.getElementById('btnMultipleSelect').addEventListener('click', Photo.Admin.startSelection);
    document.getElementById('btnStopMultipleSelect').addEventListener('click', Photo.Admin.cancelSelection);
    document.getElementById('generateCoverButton').addEventListener('click', Photo.Admin.regenerateCover);
    document.getElementById('deleteAlbumButton').addEventListener('click', Photo.Admin.deleteAlbum);
    document.getElementById('multipleDeleteButton').addEventListener('click', Photo.Admin.deleteMultiple);
    document.getElementById('multipleMoveButton').addEventListener('click', Photo.Admin.moveMultiple);
    document.getElementById('moveAlbumButton').addEventListener('click', Photo.Admin.moveAlbum);
}

Photo.Admin.startSelection = function() {
    document.getElementById('btnMultipleSelect').classList.add('btn-hidden');
    document.getElementById('btnStopMultipleSelect').classList.remove('btn-hidden');
    document.getElementById('btnMultipleDelete').classList.remove('btn-hidden');
    document.getElementById('btnMultipleMove').classList.remove('btn-hidden');

    let thumbnails = document.querySelectorAll('.pswp-gallery__item');
    thumbnails.forEach(function(element) {
        element.classList.remove('pswp-gallery__item');
        element.classList.add('selectable-photo');
        element.addEventListener('click', Photo.Admin.itemSelected);
    });
}

Photo.Admin.cancelSelection = function() {
    document.getElementById('btnMultipleSelect').classList.remove('btn-hidden');
    document.getElementById('btnStopMultipleSelect').classList.add('btn-hidden');
    document.getElementById('btnMultipleDelete').classList.add('btn-hidden');
    document.getElementById('btnMultipleMove').classList.add('btn-hidden');

    Photo.Admin.clearSelection();

    let thumbnails = document.querySelectorAll('.selectable-photo');
    thumbnails.forEach(function(element) {
        element.removeEventListener('click', Photo.Admin.itemSelected);
        element.classList.remove('selectable-photo');
        element.classList.add('pswp-gallery__item');
    });
}

Photo.Admin.itemSelected = function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (this.classList.toggle('selected')) {
        Photo.Admin.selectedCount++;
    } else {
        Photo.Admin.selectedCount--;
    }

    let counts = document.querySelectorAll('.selectedCount');
    counts.forEach(function(element) {
        element.textContent = Photo.Admin.selectedCount;
    });
}

Photo.Admin.clearSelection = function() {
    let selectedPhotos = document.querySelectorAll('.selectable-photo.selected');
    selectedPhotos.forEach(function(element) {
        element.classList.remove('selected');
    });

    Photo.Admin.selectedCount = 0;

    let counts = document.querySelectorAll('.selectedCount');
    counts.forEach(function(element) {
        element.textContent = "0";
    });
}
