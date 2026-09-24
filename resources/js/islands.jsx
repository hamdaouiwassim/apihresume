/**
 * React islands loader.
 *
 * Blade renders   <div data-island="resume-editor" data-props='{"...":"..."}'>placeholder</div>
 * and this file mounts the matching React component into it. Each island gets the same providers the
 * former SPA had (auth user, language, router), so the original page components run unchanged.
 */
import { StrictMode, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, useLocation } from 'react-router-dom';
import { AuthProvider } from './react/context/AuthContext';
import { LanguageProvider } from './react/context/LanguageContext';

const registry = {
    'resume-editor': {
        paths: ['/resume/edit/:id'],
        load: () => import('./react/pages/EditResume'),
    },
    'cover-letter-editor': {
        paths: ['/cover-letter/create', '/cover-letter/edit/:id'],
        load: () => import('./react/pages/EditCoverLetter'),
    },
    'work-certificate-editor': {
        paths: ['/work-certificate/create', '/work-certificate/edit/:id'],
        load: () => import('./react/pages/EditWorkCertificate'),
    },
    'shared-resume': {
        paths: ['/share/:token'],
        load: () => import('./react/pages/SharedResumeView'),
    },
    'template-preview': {
        load: () => import('./react/islands/TemplateSamplePreview'),
    },
    'admin-blog-editor': {
        paths: ['/admin/blog/new', '/admin/blog/edit/:id'],
        load: () => import('./react/pages/admin/AdminBlogPostEditor'),
    },
    'admin-ai-usage': {
        paths: ['/admin/ai-usage'],
        load: () => import('./react/pages/admin/AdminAiUsage'),
    },
    'admin-generated-cvs': {
        paths: ['/admin/cvs'],
        load: () => import('./react/pages/admin/GeneratedCV'),
    },
    'admin-resume-preview': {
        load: () => import('./react/islands/AdminResumePreviewIsland'),
    },
};

/** Any in-island navigation to a path outside the island becomes a normal page load (Blade route). */
function LeaveIsland() {
    const location = useLocation();
    useEffect(() => {
        // Keep toasts raised just before navigating (e.g. "Saved") visible on the next page.
        const items = window.Alpine?.store('toasts')?.items || [];
        items
            .filter((t) => t.visible && t.type !== 'loading')
            .forEach((t) => window.toast?.next(t.type === 'default' ? 'info' : t.type, t.message));
        window.location.assign(location.pathname + location.search + location.hash);
    }, [location]);
    return null;
}

function IslandShell({ entry, Component, props }) {
    const content = <Component {...props} />;

    return (
        <AuthProvider>
            <LanguageProvider>
                <BrowserRouter>
                    {entry.paths ? (
                        <Routes>
                            {entry.paths.map((path) => (
                                <Route key={path} path={path} element={content} />
                            ))}
                            <Route path="*" element={<LeaveIsland />} />
                        </Routes>
                    ) : (
                        content
                    )}
                </BrowserRouter>
            </LanguageProvider>
        </AuthProvider>
    );
}

async function mount(el) {
    const name = el.dataset.island;
    const entry = registry[name];
    if (!entry || el.dataset.islandMounted) return;
    el.dataset.islandMounted = '1';

    let props = {};
    try {
        props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
    } catch {
        props = {};
    }

    const mod = await entry.load();
    const Component = mod.default;

    createRoot(el).render(
        <StrictMode>
            <IslandShell entry={entry} Component={Component} props={props} />
        </StrictMode>,
    );
}

document.querySelectorAll('[data-island]').forEach((el) => {
    mount(el).catch((error) => {
        console.error(`Failed to mount island "${el.dataset.island}"`, error);
    });
});
