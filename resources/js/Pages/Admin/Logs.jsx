import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Pagination';

const levelClasses = {
  info: 'bg-blue-100 text-blue-800  ',
  notice: 'bg-gray-100 text-gray-800  ',
  warning: 'bg-yellow-100 text-yellow-800  ',
  error: 'bg-red-100 text-red-800  ',
  critical: 'bg-purple-100 text-purple-800  ',
};

export default function Logs({ logs, filters }) {
  const items = Array.isArray(logs) ? logs : logs?.data || [];

  const messages = usePage().props.translations?.messages || {};

  /**
   * Handle page navigation using POST to hide all URL parameters
   */
  function handlePageChange(page) {
    router.post(
      window.route ? window.route('admin.logs.index') : '/admin/logs',
      { page },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['logs', 'filters'],
      }
    );
  }

  return (
    <AuthenticatedLayout>
      <div className="px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <h1 className="text-2xl font-bold text-black ">{messages['admin.logs.title'] || 'Activity log'}</h1>
        </div>

        <div className="overflow-x-auto bg-white p-4 rounded-lg shadow">
          {/* Mobile stacked list */}
          <div className="space-y-4 md:hidden">
            {items.map((log) => {
              const levelName = (log.level || log.properties?.level || log.event || 'info').toString().toLowerCase();
              const actionName = (log.properties?.action || log.description || log.event || '').toString();
              const userName = log.causer?.name || messages['admin.logs.user'] || 'System';
              const contentObj = log.properties?.content ? log.properties.content : (log.properties || {});
              const prettyContent = JSON.stringify(contentObj, null, 2);
              const ip = log.properties?.ip || log.ip || '';

              // Get translated level name
              const levelTranslations = {
                info: messages['admin.logs.level_info'] || 'Info',
                notice: messages['admin.logs.level_notice'] || 'Notice',
                warning: messages['admin.logs.level_warning'] || 'Avertissement',
                error: messages['admin.logs.level_error'] || 'Erreur',
                critical: messages['admin.logs.level_critical'] || 'Critique',
              };
              const translatedLevel = levelTranslations[levelName] || levelName;

              return (
                <div key={log.id} className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                  {/* Header with level badge and timestamp */}
                  <div className="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b border-gray-200">
                    <div className="flex items-center justify-between mb-2">
                      <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${levelClasses[levelName] || 'bg-gray-100 text-gray-800'}`}>
                        <span className="uppercase">{translatedLevel}</span>
                      </span>
                      <div className="flex items-center text-xs text-gray-600">
                        <svg className="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {new Date(log.created_at).toLocaleString('fr-FR')}
                      </div>
                    </div>
                    <div className="text-sm font-semibold text-gray-900">{actionName}</div>
                  </div>

                  {/* Log details */}
                  <div className="px-4 py-3 space-y-3 bg-white">
                    {/* User and IP */}
                    <div className="flex items-center justify-between text-sm">
                      <div className="flex items-center text-gray-700">
                        <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span className="font-medium">{userName}</span>
                      </div>
                      {ip && (
                        <div className="flex items-center text-gray-500 text-xs">
                          <svg className="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                          </svg>
                          {ip}
                        </div>
                      )}
                    </div>

                    {/* Content */}
                    <div className="bg-gray-50 border border-gray-200 rounded-md p-3">
                      <div className="flex items-center mb-2">
                        <svg className="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span className="text-xs font-medium text-gray-600">{messages['admin.logs.details'] || 'Détails'}</span>
                      </div>
                      <pre className="font-mono text-xs text-gray-800 max-h-40 overflow-auto whitespace-pre-wrap break-words">{prettyContent}</pre>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Table for md+ */}
          {items.length ? (
            <table className="hidden md:table min-w-full text-sm align-middle">
              <thead className="bg-gray-50 border-b-2 border-gray-200">
                <tr className="text-left">
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.timestamp'] || 'Timestamp'}</th>
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.level'] || 'Level'}</th>
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.action'] || 'Action'}</th>
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.user'] || 'User'}</th>
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.content'] || 'Content'}</th>
                  <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.logs.ip'] || 'IP'}</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {items.map((log) => {
                  const levelName = (log.level || log.properties?.level || log.event || 'info').toString().toLowerCase();
                  const actionName = (log.properties?.action || log.description || log.event || '').toString();
                  const userName = log.causer?.name || messages['admin.logs.user'] || 'Système';
                  const contentObj = log.properties?.content ? log.properties.content : (log.properties || {});
                  const prettyContent = JSON.stringify(contentObj, null, 2);
                  const ip = log.properties?.ip || log.ip || '';

                  // Get translated level name
                  const levelTranslations = {
                    info: messages['admin.logs.level_info'] || 'Info',
                    notice: messages['admin.logs.level_notice'] || 'Notice',
                    warning: messages['admin.logs.level_warning'] || 'Avertissement',
                    error: messages['admin.logs.level_error'] || 'Erreur',
                    critical: messages['admin.logs.level_critical'] || 'Critique',
                  };
                  const translatedLevel = levelTranslations[levelName] || levelName;

                  return (
                    <tr key={log.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-4 text-gray-900 whitespace-nowrap">
                        <div className="flex items-center text-sm">
                          <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          {new Date(log.created_at).toLocaleString('fr-FR')}
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${levelClasses[levelName] || 'bg-gray-100 text-gray-800'}`}>
                          <span className="uppercase">{translatedLevel}</span>
                        </span>
                      </td>
                      <td className="px-4 py-4 text-gray-900 font-medium">{actionName}</td>
                      <td className="px-4 py-4 text-gray-700">
                        <div className="flex items-center">
                          <svg className="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          {userName}
                        </div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        <pre className="font-mono text-xs bg-gray-50 text-gray-800 p-3 rounded-md border border-gray-200 max-w-[40ch] max-h-40 overflow-auto whitespace-pre-wrap break-words">{prettyContent}</pre>
                      </td>
                      <td className="px-4 py-4 text-gray-600 text-xs font-mono">{ip}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ) : (
            <p className="text-gray-600 text-center py-8">{messages['admin.logs.no_activity'] || 'No activity for now.'}</p>
          )}
        </div>

        <Pagination pagination={logs} onPageChange={handlePageChange} />
      </div>
    </AuthenticatedLayout>
  );
}
