import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const levelClasses = {
  info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
  notice: 'bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-gray-100',
  warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
  error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
  critical: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
};

const LEVELS = ['info', 'notice', 'warning', 'error', 'critical'];

export default function Logs({ logs }) {
  const items = Array.isArray(logs) ? logs : logs?.data || [];
  const meta = logs?.meta || null;

  {/*
  const [q, setQ] = useState('');
  const [level, setLevel] = useState('');
  */}

  const messages = usePage().props.translations?.messages || {};

  function submitFilters(params = {}) {
    const query = { q, level, ...params };
    router.get(window.route ? window.route('admin.logs') : '/admin/logs', query, { preserveState: true, replace: true });
  }

  return (
    <AuthenticatedLayout>
      <div className="px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <h1 className="text-2xl font-bold text-black dark:text-white">{messages['admin.logs.title'] || 'Activity log'}</h1>
        </div>

        {/*}
        <div className="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div className="flex-1 flex flex-col sm:flex-row sm:items-center gap-2">
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder={messages['admin.logs.search_placeholder'] || 'Search description...'}
              className="rounded-md border px-3 py-2 text-sm bg-white dark:bg-zinc-800 dark:text-white w-full sm:w-64"
            />
            <select value={level} onChange={(e) => setLevel(e.target.value)} className="rounded-md border ps-3 py-2 text-sm bg-white dark:bg-zinc-800 dark:text-white">
              <option value="">{messages['admin.logs.level_all'] || 'All levels'}</option>
              {LEVELS.map((l) => (
                <option key={l} value={l}>{l}</option>
              ))}
            </select>
            <div className="flex gap-2">
              <button onClick={() => submitFilters({ page: 1 })} className="rounded-md bg-gray-800 text-white px-3 py-2 text-sm">{messages['admin.users.filter'] || 'Filter'}</button>
              <button onClick={() => { setQ(''); setLevel(''); submitFilters({ page: 1, q: '', level: '' }); }} className="rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200">{messages['admin.users.reset'] || 'Reset'}</button>
            </div>
          </div>
        </div>
        */}

        <div className="overflow-x-auto bg-white dark:bg-zinc-900 p-4 rounded-lg shadow">
          {/* Mobile stacked list */}
          <div className="space-y-3 md:hidden">
            {items.map((log) => {
              const levelName = (log.level || log.properties?.level || log.event || 'info').toString().toLowerCase();
              const actionName = (log.properties?.action || log.description || log.event || '').toString();
              const userName = log.causer?.name || 'System';
              const contentObj = log.properties?.content ? log.properties.content : (log.properties || {});
              const prettyContent = JSON.stringify(contentObj, null, 2);
              const ip = log.properties?.ip || log.ip || '';

              return (
                <div key={log.id} className="p-3 bg-gray-50 dark:bg-zinc-800 rounded border border-gray-100 dark:border-zinc-700">
                  <div className="flex items-center justify-between mb-2">
                    <div className="text-sm text-gray-700 dark:text-gray-200">{new Date(log.created_at).toLocaleString()}</div>
                    <div className="text-xs font-medium inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-100">{levelName}</div>
                  </div>
                  <div className="text-sm text-gray-700 dark:text-gray-200 mb-2">{actionName}</div>
                  <div className="text-sm text-gray-600 dark:text-gray-300 mb-2">{userName} — {ip}</div>
                  <pre className="font-mono text-xs bg-gray-50 dark:bg-zinc-900 text-gray-800 dark:text-gray-100 p-2 rounded max-h-40 overflow-auto whitespace-pre-wrap break-words">{prettyContent}</pre>
                </div>
              );
            })}
          </div>

          {/* Table for md+ */}
          {items.length ? (
            <table className="hidden md:table min-w-full text-sm align-middle divide-y divide-gray-200 dark:divide-zinc-700">
              <thead>
                <tr className="text-left">
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.timestamp'] || 'Timestamp'}</th>
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.level'] || 'Level'}</th>
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.action'] || 'Action'}</th>
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.user'] || 'User'}</th>
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.content'] || 'Content'}</th>
                  <th className="px-3 py-2 text-xs text-gray-500 dark:text-gray-300">{messages['admin.logs.ip'] || 'IP'}</th>
                </tr>
              </thead>
              <tbody>
                {items.map((log) => {
                  const levelName = (log.level || log.properties?.level || log.event || 'info').toString().toLowerCase();
                  const actionName = (log.properties?.action || log.description || log.event || '').toString();
                  const userName = log.causer?.name || 'Système';
                  const contentObj = log.properties?.content ? log.properties.content : (log.properties || {});
                  const prettyContent = JSON.stringify(contentObj, null, 2);
                  const ip = log.properties?.ip || log.ip || '';

                  return (
                    <tr key={log.id} className="bg-white dark:bg-zinc-900">
                      <td className="px-3 py-3 text-gray-900 dark:text-gray-100 whitespace-nowrap">{new Date(log.created_at).toLocaleString()}</td>
                      <td className="px-3 py-3 whitespace-nowrap">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${levelClasses[levelName] || 'bg-gray-100 text-gray-800'}`}>
                          {levelName}
                        </span>
                      </td>
                      <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{actionName}</td>
                      <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{userName}</td>
                      <td className="px-3 py-3 text-gray-900 dark:text-gray-100 align-top">
                        <pre className="font-mono text-xs bg-gray-50 dark:bg-zinc-900 text-gray-800 dark:text-gray-100 p-2 rounded max-w-[40ch] max-h-40 overflow-auto whitespace-pre-wrap break-words">{prettyContent}</pre>
                      </td>
                      <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{ip}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          ) : (
            <p className="text-gray-600 dark:text-gray-300">{messages['admin.logs.no_activity'] || 'No activity for now.'}</p>
          )}
        </div>

        {meta && (
          <div className="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div className="text-sm text-gray-600 dark:text-gray-300">{(messages['admin.logs.page_info'] || 'Page :current / :last — :total entries').replace(':current', meta.current_page).replace(':last', meta.last_page).replace(':total', meta.total)}</div>
            <div className="flex gap-2">
              <button
                disabled={!meta.prev_page_url}
                onClick={() => submitFilters({ page: Math.max(1, meta.current_page - 1) })}
                className="rounded-md border px-3 py-2 text-sm disabled:opacity-50"
              >Précédent</button>
              <button
                disabled={!meta.next_page_url}
                onClick={() => submitFilters({ page: meta.current_page + 1 })}
                className="rounded-md border px-3 py-2 text-sm disabled:opacity-50"
              >Suivant</button>
            </div>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
