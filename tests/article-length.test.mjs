import {readFile,writeFile,unlink} from 'node:fs/promises';
import ts from 'typescript';
import assert from 'node:assert/strict';
const path=new URL('../lib/editorial/.test-article.mjs',import.meta.url);
await writeFile(path,ts.transpileModule(await readFile(new URL('../lib/editorial/article.ts',import.meta.url),'utf8'),{compilerOptions:{module:ts.ModuleKind.ESNext,target:ts.ScriptTarget.ES2022}}).outputText);
try{const {articleWordCount,articleLengthError}=await import(path.href);
 assert.equal(articleWordCount('One two\n\nSource: https://example.org/'),2);
 assert.equal(articleWordCount('One two\n\nSources\nCouncil information and links'),2);
 assert.equal(articleWordCount('One https://example.org two'),2);
 assert.ok(articleLengthError('word '.repeat(399)));assert.equal(articleLengthError('word '.repeat(400)),null);
 assert.ok(articleLengthError('word '.repeat(399)+'\nSources\n'+'citation '.repeat(500)));
 for(const route of ['editorial','publishing'])assert.match(await readFile(new URL('../app/api/'+route+'/route.ts',import.meta.url),'utf8'),/articleLengthError\(/);
 console.log('400-word boundary and source exclusion passed; both server routes enforce length.');
}finally{await unlink(path);}
