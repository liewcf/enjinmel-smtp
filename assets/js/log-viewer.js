jQuery(document).ready(function($) {
	'use strict';

	var logViewer = {
		init: function() {
			this.bindEvents();
			this.syncCheckboxes();
		},

		bindEvents: function() {
			$('#cb-select-all-1, #cb-select-all-2').on('change', this.toggleAllCheckboxes);
			$('#doaction').on('click', this.handleBulkAction);
			$('#export-csv').on('click', this.handleExport);
			$('#clear-all-logs').on('click', this.handleClearAll);
			$('.enjinmel-logs-table tbody').on('change', 'input[type="checkbox"]', this.syncSelectAll);
		},

		toggleAllCheckboxes: function() {
			var isChecked = $(this).prop('checked');
			$('.enjinmel-logs-table tbody input[type="checkbox"]').prop('checked', isChecked);
			$('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
		},

		syncSelectAll: function() {
			var totalCheckboxes = $('.enjinmel-logs-table tbody input[type="checkbox"]').length;
			var checkedCheckboxes = $('.enjinmel-logs-table tbody input[type="checkbox"]:checked').length;
			var isAllChecked = totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0;
			$('#cb-select-all-1, #cb-select-all-2').prop('checked', isAllChecked);
		},

		syncCheckboxes: function() {
			this.syncSelectAll();
		},

		handleBulkAction: function(e) {
			e.preventDefault();
			
			var action = $('#bulk-action-selector-top').val();
			
			if (action === '-1') {
				return;
			}
			
			if (action === 'delete') {
				logViewer.deleteSelectedLogs();
			}
		},

		deleteSelectedLogs: function() {
			var selectedLogs = [];
			$('.enjinmel-logs-table tbody input[type="checkbox"]:checked').each(function() {
				selectedLogs.push($(this).val());
			});

			if (selectedLogs.length === 0) {
				alert(enjinmelSmtpLogViewer.strings.noLogsSelected);
				return;
			}

			if (!confirm(enjinmelSmtpLogViewer.strings.confirmDelete)) {
				return;
			}

			$.ajax({
				url: enjinmelSmtpLogViewer.ajaxurl,
				type: 'POST',
				data: {
					action: 'enjinmel_smtp_delete_logs',
					nonce: enjinmelSmtpLogViewer.nonce,
					log_ids: selectedLogs
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data.message || enjinmelSmtpLogViewer.strings.deleteFailed);
					}
				},
				error: function() {
					alert(enjinmelSmtpLogViewer.strings.deleteFailed);
				}
			});
		},

		handleExport: function(e) {
			e.preventDefault();
			
			var currentUrl = window.location.href;
			var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
			var exportUrl = currentUrl + separator + 'action=enjinmel_smtp_export_logs&nonce=' + enjinmelSmtpLogViewer.nonce;
			
			window.location.href = exportUrl;
		},

		handleClearAll: function(e) {
			e.preventDefault();
			
			if (!confirm(enjinmelSmtpLogViewer.strings.confirmClearAll)) {
				return;
			}

			$.ajax({
				url: enjinmelSmtpLogViewer.ajaxurl,
				type: 'POST',
				data: {
					action: 'enjinmel_smtp_clear_all_logs',
					nonce: enjinmelSmtpLogViewer.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(enjinmelSmtpLogViewer.strings.clearAllSuccess);
						location.reload();
					} else {
						alert(response.data.message || enjinmelSmtpLogViewer.strings.clearAllFailed);
					}
				},
				error: function() {
					alert(enjinmelSmtpLogViewer.strings.clearAllFailed);
				}
			});
		}
	};

	logViewer.init();
});
