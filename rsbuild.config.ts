/**
 * RSBuild Configuration for Data Definitions Studio Plugin
 */

import { defineConfig } from '@rsbuild/core'
import { pluginReact } from '@rsbuild/plugin-react'
import { pluginSvgr } from '@rsbuild/plugin-svgr'
import { pluginModuleFederation } from '@module-federation/rsbuild-plugin'
import { pluginGenerateEntrypoints } from '@pimcore/studio-ui-bundle/rsbuild/plugins'
import path from 'path'
import fs from 'fs'
import crypto from 'crypto'

/**
 * Build id, derived from the sources.
 *
 * The id is the output directory, the public asset prefix and the archive name
 * (build-dist/build-<id>.zip), so it has to change whenever the emitted assets change — and
 * only then. Inputs: everything under the Studio assets directory (except node_modules and
 * build caches), package.json, package-lock.json and this config. PIMCORE_BUILD_ID overrides
 * it, the dev server uses `dev`.
 */
function collectFiles (dir: string, files: string[] = []): string[] {
  const ignored = new Set(['node_modules', 'dist', '.rsbuild', '.turbo', '@mf-types'])
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (ignored.has(entry.name)) continue
    const full = path.resolve(dir, entry.name)
    if (entry.isDirectory()) collectFiles(full, files)
    else if (entry.isFile()) files.push(full)
  }
  return files
}

function computeBuildId (): string {
  const hash = crypto.createHash('sha256')
  const inputs = [
    ...['package.json', 'package-lock.json', 'rsbuild.config.ts'].map((f) => path.resolve(__dirname, f)),
    ...collectFiles(path.resolve(__dirname, 'src/DataDefinitionsBundle/Resources/assets/pimcore-studio')).sort()
  ]
  for (const file of inputs) {
    if (!fs.existsSync(file)) continue
    hash.update(path.relative(__dirname, file).split(path.sep).join('/') + '\0')
    hash.update(fs.readFileSync(file))
  }
  return hash.digest('hex').slice(0, 32)
}

const buildId = process.env.PIMCORE_BUILD_ID || (process.env.NODE_ENV === 'dev-server' ? 'dev' : computeBuildId())
const bundlePrefix = 'datadefinitions'
const studioAssetsPath = path.resolve(__dirname, 'src/DataDefinitionsBundle/Resources/assets/pimcore-studio')
const buildPath = path.resolve(__dirname, 'src/DataDefinitionsBundle/Resources/public/studio', buildId)
const entryFile = path.resolve(studioAssetsPath, 'src/main.ts')

// Clean old build directories
const studioPath = path.resolve(__dirname, 'src/DataDefinitionsBundle/Resources/public/studio')
if (fs.existsSync(studioPath)) {
  fs.readdirSync(studioPath).forEach((file) => {
    const filePath = path.resolve(studioPath, file)
    if (fs.statSync(filePath).isDirectory()) {
      fs.rmSync(filePath, { recursive: true, force: true })
    }
  })
}

// Ensure build directory exists
if (!fs.existsSync(buildPath)) {
  fs.mkdirSync(buildPath, { recursive: true })
}
// studio-package-build reads the id of the build it packages from this file
fs.writeFileSync(path.join(buildPath, '.build-id'), buildId + '\n')

let nodeEnv = process.env.NODE_ENV
let env: 'development' | 'production' = 'production'

const isDevServer = nodeEnv === 'dev-server'
if (nodeEnv !== 'production') {
  env = 'development'
}

const devPort = 3050

export default defineConfig({
  mode: env,
  root: studioAssetsPath,
  server: {
    port: devPort,
    publicDir: {
      copyOnBuild: false
    }
  },
  dev: {
    ...(!isDevServer ? { assetPrefix: `/bundles/${bundlePrefix}/studio/${buildId}` } : {}),
    client: {
      host: 'localhost',
      port: devPort,
      protocol: 'ws'
    },
    hmr: true,
  },
  source: {
    entry: {
      main: entryFile
    },
    decorators: {
      version: 'legacy'
    }
  },
  resolve: {
    alias: {
      '@DataDefinitions': path.resolve(studioAssetsPath, 'src'),
      '@DataDefinitions/assets': path.resolve(studioAssetsPath, 'src/assets')
    }
  },
  output: {
    manifest: true,
    assetPrefix: `/bundles/${bundlePrefix}/studio/${buildId}`,
    distPath: {
      root: buildPath
    }
  },
  tools: {
    bundlerChain: (chain, { env }) => {
      chain.output.uniqueName(bundlePrefix)
    }
  },
  plugins: [
    pluginGenerateEntrypoints(),
    pluginReact(),
    pluginSvgr({
      svgrOptions: {
        icon: true,
        typescript: true
      }
    }),
    pluginModuleFederation({
      name: bundlePrefix,
      filename: 'static/js/remoteEntry.js',
      exposes: {
        '.': entryFile
      },
      dts: false,
      remotes: {
        '@pimcore/studio-ui-bundle': `promise new Promise(resolve => {
          const studioUIBundleRemoteUrl = window.StudioUIBundleRemoteUrl
          const script = document.createElement('script')

          let hasScript = false;

          document.querySelectorAll('script').forEach((el) => {
            const elPathname = el.src.replace(/https?:\\/\\/[^/]+/, '')
            const studioUIBundleRemoteUrlPathname = studioUIBundleRemoteUrl.replace(/https?:\\/\\/[^/]+/, '')

            if (elPathname === studioUIBundleRemoteUrlPathname) {
              hasScript = true;
              return;
            }
          })

          if (hasScript) {
            resolve({
              get: (request) => window['pimcore_studio_ui_bundle'].get(request),
              init: (...arg) => {
                try {
                  return window['pimcore_studio_ui_bundle'].init(...arg)
                } catch(e) {
                  console.log('remote container already initialized')
                }
              }
            })
            return
          }

          script.src = studioUIBundleRemoteUrl
          script.onload = () => {
            const proxy = {
              get: (request) => window['pimcore_studio_ui_bundle'].get(request),
              init: (...arg) => {
                try {
                  return window['pimcore_studio_ui_bundle'].init(...arg)
                } catch(e) {
                  console.log('remote container already initialized')
                }
              }
            }
            resolve(proxy)
          }
          document.head.appendChild(script);
        })
        `
      },
      shared: {
        react: {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        'react-dom': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        'react/jsx-runtime': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        'react/jsx-dev-runtime': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        'react-i18next': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        'i18next': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        '@emotion/react': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        '@emotion/styled': {
          singleton: true,
          eager: false,
          requiredVersion: false,
          strictVersion: false
        },
        antd: {
          singleton: true,
          eager: false,
          requiredVersion: false
        },
        '@reduxjs/toolkit': {
          singleton: true,
          eager: false,
          requiredVersion: false
        },
        'react-redux': {
          singleton: true,
          eager: false,
          requiredVersion: false
        },
        immer: {
          singleton: true,
          eager: false,
          requiredVersion: false
        },
        zustand: {
          singleton: true,
          eager: false,
          requiredVersion: false
        }
      }
    })
  ]
})
