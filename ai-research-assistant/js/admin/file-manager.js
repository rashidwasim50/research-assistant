(function($) {
    'use strict';

    class FileManager {
        constructor() {
            this.uploadButton = $('#upload-new-pages');
            this.deleteButton = $('#delete-files');
            this.updateButton = $('#update-files');
            this.deleteBothButton = $('#delete-both-files');
            this.nonce = fileManagerAjax.nonce;
            this.init();
        }

        refreshTables() {
            const data = new FormData();
            data.append('action', 'get_file_tables');
            data.append('nonce', this.nonce);

            return $.ajax({
                url: fileManagerAjax.ajaxUrl,
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        $('#files-only-in-site-pages-table').html(response.data.files_only_in_site_pages);
                        $('#files-only-in-pinecone-files-table').html(response.data.files_only_in_pinecone);
                        $('#files-with-newer-website-timestamp-table').html(response.data.files_with_newer_timestamps);
                        this.initCheckboxHandlers();
                    } else {
                        alert(`Error refreshing tables: ${response.data}`);
                    }
                },
                error: (xhr, status, error) => {
                    error_log('AJAX Error:', xhr.responseText);
                    alert(`Error refreshing tables: ${error}`);
                }
            });
        }
        
        init() {
            this.initCheckboxHandlers();
            this.initButtonHandlers();
        }

        initCheckboxHandlers() {
            // Upload checkboxes
            this.handleCheckboxState('.file-checkbox-upload', '#upload-new-pages');
            
            // Delete checkboxes for Files Only in Pinecone
            this.handleCheckboxState('.file-checkbox-delete', '#delete-files');
            
            // Update checkboxes
            this.handleCheckboxState('.file-checkbox-update', '#update-files');
            
            // Delete checkboxes for Files In Both
            this.handleCheckboxState('.file-checkbox-both', '#delete-both-files');
        }

        handleCheckboxState(checkboxClass, buttonId) {
            const checkboxes = document.querySelectorAll(checkboxClass);
            const button = document.getElementById(buttonId.substring(1));

            const updateButtonState = () => {
                const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
                button.disabled = !anyChecked;
            };

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateButtonState);
            });

            // Initial state
            updateButtonState();
        }

        initButtonHandlers() {
            this.uploadButton.on('click', () => this.handleUpload());
            this.deleteButton.on('click', () => this.handleDelete());
            this.updateButton.on('click', () => this.handleUpdate());
            this.deleteBothButton.on('click', () => this.handleDeleteBoth());
        }

        handleUpload() {
            this.handleAjaxRequest({
                button: this.uploadButton,
                action: 'upload_new_pages',
                formId: 'files-only-in-site-pages-form',
                successMessage: 'Files uploaded successfully!'
            });
        }

        handleDelete() {
            this.handleAjaxRequest({
                button: this.deleteButton,
                action: 'delete_files',
                formId: 'files-only-in-pinecone-form',
                successMessage: 'Files deleted successfully!'
            });
        }

        handleUpdate() {
            this.handleAjaxRequest({
                button: this.updateButton,
                action: 'update_files',
                formId: 'files-with-newer-timestamps-form',
                successMessage: 'Files updated successfully!'
            });
        }

        handleDeleteBoth() {
            this.handleAjaxRequest({
                button: this.deleteBothButton,
                action: 'delete_both_files',
                formId: 'files-in-both-form',
                successMessage: 'Files deleted successfully!'
            });
        }

        handleAjaxRequest({ button, action, formId, successMessage }) {
            button.prop('disabled', true);
            
            const formData = new FormData(document.getElementById(formId));
            formData.append('action', action);
            formData.append('nonce', fileManagerAjax.nonce);

            $.ajax({
                url: fileManagerAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        alert(successMessage);
                        this.refreshTables();
                    } else {
                        alert(`Error: ${response.data}`);
                    }
                },
                error: (xhr, status, error) => {
                    alert(`Error: ${error}`);
                },
                complete: () => {
                    button.prop('disabled', false);
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new FileManager();
    });

})(jQuery);