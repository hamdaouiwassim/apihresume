/**
 * Admin Database and Assets Export & Backup Management
 */
export default function register(Alpine) {
    Alpine.data('adminExports', () => ({
        loading: true,
        stats: {
            database: {
                driver: 'sqlite',
                database_name: '',
                size_formatted: '0 B',
                total_tables: 0,
                total_records: 0,
                tables: [],
            },
            assets: {
                total_files: 0,
                total_formatted: '0 B',
                categories: [],
            },
            backups: [],
        },

        // Database export state
        dbFormat: 'sql',
        dbCompress: false,
        selectedTables: [],
        selectAllTables: true,
        exportingDb: false,

        // Assets export state
        selectedScopes: ['all'],
        exportingAssets: false,

        // Full backup state
        exportingFull: false,

        // Table search in database selector
        tableSearch: '',
        backupsSearch: '',

        init() {
            this.fetchStatus();
        },

        async fetchStatus() {
            this.loading = true;
            try {
                const { data } = await window.api.get('admin/exports/status');
                if (data.status) {
                    this.stats = data.data;
                    if (this.selectAllTables && this.stats.database?.tables) {
                        this.selectedTables = this.stats.database.tables.map((t) => t.name);
                    }
                }
            } catch (err) {
                window.toast.error('Failed to load export statistics');
            } finally {
                this.loading = false;
            }
        },

        get filteredTables() {
            const list = this.stats.database?.tables || [];
            if (!this.tableSearch.trim()) return list;
            const q = this.tableSearch.toLowerCase();
            return list.filter((t) => t.name.toLowerCase().includes(q));
        },

        get filteredBackups() {
            const list = this.stats.backups || [];
            if (!this.backupsSearch.trim()) return list;
            const q = this.backupsSearch.toLowerCase();
            return list.filter((b) => b.filename.toLowerCase().includes(q) || b.type.toLowerCase().includes(q));
        },

        toggleAllTables() {
            this.selectAllTables = !this.selectAllTables;
            if (this.selectAllTables) {
                this.selectedTables = (this.stats.database?.tables || []).map((t) => t.name);
            } else {
                this.selectedTables = [];
            }
        },

        toggleTable(name) {
            const idx = this.selectedTables.indexOf(name);
            if (idx > -1) {
                this.selectedTables.splice(idx, 1);
                this.selectAllTables = false;
            } else {
                this.selectedTables.push(name);
                if (this.selectedTables.length === (this.stats.database?.tables?.length || 0)) {
                    this.selectAllTables = true;
                }
            }
        },

        toggleScope(scope) {
            if (scope === 'all') {
                this.selectedScopes = ['all'];
                return;
            }

            const withoutAll = this.selectedScopes.filter((s) => s !== 'all');
            const idx = withoutAll.indexOf(scope);

            if (idx > -1) {
                withoutAll.splice(idx, 1);
            } else {
                withoutAll.push(scope);
            }

            this.selectedScopes = withoutAll.length > 0 ? withoutAll : ['all'];
        },

        async exportDatabase(directDownload = false) {
            if (this.selectedTables.length === 0) {
                window.toast.error('Please select at least one database table to export');
                return;
            }

            if (directDownload) {
                const params = new URLSearchParams({
                    format: this.dbFormat,
                    compress: this.dbCompress ? '1' : '0',
                    tables: this.selectedTables.join(','),
                });
                window.location.href = `/admin/exports/database/download?${params.toString()}`;
                window.toast.success('Database export download started');
                return;
            }

            this.exportingDb = true;
            try {
                const { data } = await window.api.post('admin/exports/database', {
                    format: this.dbFormat,
                    compress: this.dbCompress,
                    tables: this.selectedTables,
                });

                if (data.status) {
                    window.toast.success(data.message || 'Database export created successfully');
                    await this.fetchStatus();
                    if (data.data?.download_url) {
                        window.location.href = data.data.download_url;
                    }
                }
            } catch (err) {
                window.toast.error(err?.response?.data?.message || 'Database export failed');
            } finally {
                this.exportingDb = false;
            }
        },

        async exportAssets(directDownload = false) {
            if (directDownload) {
                const scope = this.selectedScopes[0] || 'all';
                window.location.href = `/admin/exports/assets/download?scope=${encodeURIComponent(scope)}`;
                window.toast.success('Assets archive download started');
                return;
            }

            this.exportingAssets = true;
            try {
                const { data } = await window.api.post('admin/exports/assets', {
                    scopes: this.selectedScopes,
                });

                if (data.status) {
                    window.toast.success(data.message || 'Assets archive created successfully');
                    await this.fetchStatus();
                    if (data.data?.download_url) {
                        window.location.href = data.data.download_url;
                    }
                }
            } catch (err) {
                window.toast.error(err?.response?.data?.message || 'Assets export failed');
            } finally {
                this.exportingAssets = false;
            }
        },

        async exportFull(directDownload = false) {
            if (directDownload) {
                window.location.href = '/admin/exports/full/download';
                window.toast.success('Full system backup download started');
                return;
            }

            this.exportingFull = true;
            try {
                const { data } = await window.api.post('admin/exports/full');

                if (data.status) {
                    window.toast.success(data.message || 'Full backup generated successfully');
                    await this.fetchStatus();
                    if (data.data?.download_url) {
                        window.location.href = data.data.download_url;
                    }
                }
            } catch (err) {
                window.toast.error(err?.response?.data?.message || 'Full backup generation failed');
            } finally {
                this.exportingFull = false;
            }
        },

        async deleteBackup(backup) {
            const confirmed = await window.confirmDialog({
                title: 'Delete Backup File',
                message: `Are you sure you want to permanently delete the backup file "${backup.filename}"? This action cannot be undone.`,
                confirmText: 'Delete File',
                cancelText: 'Keep File',
            });

            if (!confirmed) return;

            try {
                const { data } = await window.api.delete(`admin/exports/${encodeURIComponent(backup.filename)}`);
                if (data.status) {
                    window.toast.success('Backup file deleted');
                    this.stats.backups = this.stats.backups.filter((b) => b.filename !== backup.filename);
                }
            } catch (err) {
                window.toast.error(err?.response?.data?.message || 'Failed to delete backup file');
            }
        },

        async copyFilename(filename) {
            try {
                await navigator.clipboard.writeText(filename);
                window.toast.success('Filename copied to clipboard');
            } catch {
                window.toast.error('Failed to copy');
            }
        },
    }));
}
